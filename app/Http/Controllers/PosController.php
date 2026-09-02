<?php

namespace App\Http\Controllers;

use App\Models\DailySession;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(Request $request): View
    {
        $project = $request->user()->currentProject();

        abort_if(! $project, 404, 'Tiada projek/outlet dijumpai.');

        $session = DailySession::where('project_id', $project->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        $categories = $project->categories()
            ->with(['products' => fn ($q) => $q->where('is_active', true)->orderBy('price')->orderBy('name')])
            ->orderBy('sort_order')
            ->get();

        $todaysOrders = Order::where('project_id', $project->id)
            ->whereDate('created_at', now()->toDateString())
            ->with('items')
            ->latest('created_at')
            ->get();

        return view('pos.index', [
            'project' => $project,
            'session' => $session,
            'categories' => $categories,
            'todaysOrders' => $todaysOrders,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $project = $request->user()->currentProject();

        $session = DailySession::where('project_id', $project->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        if (! $session) {
            return response()->json(['message' => 'Buka hari dahulu sebelum boleh berjualan.'], 400);
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,qr,card'],
            'cash_received' => ['nullable', 'numeric', 'min:0'],
        ]);

        $order = $this->createOrderWithRetry(function () use ($validated, $project, $session, $request) {
            $subtotal = 0;
            $lineItems = [];

            foreach ($validated['items'] as $item) {
                $product = Product::with('category')->where('project_id', $project->id)->findOrFail($item['product_id']);

                // Variable-price products (e.g. Add-On) have no fixed catalog
                // price - staff enters it in the POS at sale time, so that
                // client-supplied price is trusted here. Fixed-price
                // products always use the server-side catalog price,
                // ignoring whatever the client sent, so it can't be tampered with.
                $unitPrice = $product->is_variable_price
                    ? (float) ($item['unit_price'] ?? 0)
                    : (float) $product->price;

                // Variable-price items are prefixed with their category
                // (e.g. "Add-On: Ayam Cincang") so the receipt still shows
                // what was actually charged for, not just a bare item name.
                $productName = $product->is_variable_price && $product->category
                    ? "{$product->category->name}: {$product->name}"
                    : $product->name;

                $lineSubtotal = $unitPrice * $item['qty'];
                $subtotal += $lineSubtotal;

                $lineItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $productName,
                    'unit_price' => $unitPrice,
                    'qty' => $item['qty'],
                    'subtotal' => $lineSubtotal,
                ];
            }

            $discount = $validated['discount'] ?? 0;
            $total = max($subtotal - $discount, 0);

            $cashReceived = null;
            if ($validated['payment_method'] === Order::PAYMENT_METHOD_CASH) {
                $cashReceived = (float) ($validated['cash_received'] ?? 0);
                abort_if($cashReceived < $total, 422, 'Tunai diterima tidak mencukupi.');
            }

            $order = Order::create([
                'project_id' => $project->id,
                'daily_session_id' => $session->id,
                'cashier_id' => $request->user()->id,
                'order_number' => $this->generateOrderNumber($project->id),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'payment_method' => $validated['payment_method'],
                'cash_received' => $cashReceived,
                'status' => Order::STATUS_COMPLETED,
            ]);

            foreach ($lineItems as $lineItem) {
                $order->items()->create($lineItem);
            }

            return $order;
        });

        return response()->json([
            'message' => 'Order berjaya!',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'receipt_url' => route('orders.receipt', $order),
        ]);
    }

    public function ping(): JsonResponse
    {
        return response()->json(['ok' => true]);
    }

    public function void(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->project_id === $request->user()->currentProject()?->id, 403);

        if ($order->isVoided()) {
            return response()->json(['message' => 'Order ini sudah dibatalkan.'], 422);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        $order->update([
            'status' => Order::STATUS_VOIDED,
            'void_reason' => $validated['reason'],
            'voided_at' => now(),
            'voided_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Order dibatalkan.',
            'voided_at' => $order->voided_at->format('d/m/Y H:i'),
        ]);
    }

    private function generateOrderNumber(int $projectId): string
    {
        // order_number is unique across the whole project (not scoped per
        // day), so the sequence must keep climbing across days too -
        // resetting to "today's count" would collide with an earlier day's
        // SB001 the moment more than one calendar day of orders exists.
        // Read the highest existing suffix (not a row count) so a deleted
        // test order never reopens a number that's still in use above it.
        $maxNumber = Order::where('project_id', $projectId)
            ->where('order_number', 'like', 'SB%')
            ->pluck('order_number')
            ->map(fn ($number) => (int) substr($number, 2))
            ->max();

        return sprintf('SB%03d', ($maxNumber ?? 0) + 1);
    }

    /**
     * Two checkouts landing at the same instant can both read the same
     * highest order_number and generate the same next one, so the second
     * insert hits the unique constraint. Retry a few times on that
     * specific collision - each retry re-reads the max and gets a fresh
     * number - instead of surfacing a 500 to the cashier.
     */
    private function createOrderWithRetry(\Closure $callback, int $maxAttempts = 5): Order
    {
        $attempt = 0;

        while (true) {
            try {
                return DB::transaction($callback);
            } catch (\Illuminate\Database\QueryException $e) {
                $attempt++;
                $isOrderNumberCollision = str_contains($e->getMessage(), 'orders_order_number_unique');

                if (! $isOrderNumberCollision || $attempt >= $maxAttempts) {
                    throw $e;
                }
            }
        }
    }
}
