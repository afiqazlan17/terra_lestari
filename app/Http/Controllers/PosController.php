<?php

namespace App\Http\Controllers;

use App\Models\DailySession;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
            ->with(['products' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return view('pos.index', [
            'project' => $project,
            'session' => $session,
            'categories' => $categories,
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
            'discount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,qr,card'],
            'order_type' => ['required', Rule::in(array_keys(Order::TYPES))],
        ]);

        $order = DB::transaction(function () use ($validated, $project, $session, $request) {
            $subtotal = 0;
            $lineItems = [];

            foreach ($validated['items'] as $item) {
                $product = Product::where('project_id', $project->id)->findOrFail($item['product_id']);
                $lineSubtotal = $product->price * $item['qty'];
                $subtotal += $lineSubtotal;

                $lineItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'qty' => $item['qty'],
                    'subtotal' => $lineSubtotal,
                ];
            }

            $discount = $validated['discount'] ?? 0;
            $total = max($subtotal - $discount, 0);

            $order = Order::create([
                'project_id' => $project->id,
                'daily_session_id' => $session->id,
                'cashier_id' => $request->user()->id,
                'order_number' => $this->generateOrderNumber($project->id),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'payment_method' => $validated['payment_method'],
                'order_type' => $validated['order_type'],
                'status' => 'completed',
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

    private function generateOrderNumber(int $projectId): string
    {
        $countToday = Order::where('project_id', $projectId)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return sprintf('SB%03d', $countToday + 1);
    }
}
