<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\StoresReceipts;
use App\Models\NbkOrder;
use App\Models\NbkProduct;
use App\Models\Project;
use App\Models\Purchase;
use App\Rules\ValidReceiptFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NbkOrderController extends Controller
{
    use StoresReceipts;

    public function index(Request $request): View
    {
        $project = $request->user()->currentProject();

        $orders = $project->nbkOrders()
            ->with(['createdBy', 'paidBy'])
            ->latest('order_date')
            ->latest('id')
            ->paginate(20);

        return view('nbk.orders.index', [
            'project' => $project,
            'orders' => $orders,
        ]);
    }

    public function create(Request $request): View
    {
        $project = $request->user()->currentProject();

        $products = $project->nbkProducts()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('nbk.orders.create', [
            'project' => $project,
            'products' => $products,
            'order' => null,
            'qtyByProductId' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $project = $request->user()->currentProject();
        $validated = $this->validateItems($request);

        $order = DB::transaction(function () use ($validated, $project, $request) {
            [$lineItems, $totalBuy, $totalSell] = $this->buildLineItems($validated['items'], $project);

            abort_if(empty($lineItems), 422, 'Tiada produk dengan kuantiti order.');

            $order = $project->nbkOrders()->create([
                'created_by' => $request->user()->id,
                'order_date' => $validated['order_date'],
                'total_buy' => $totalBuy,
                'total_sell' => $totalSell,
                'total_profit' => $totalSell - $totalBuy,
            ]);

            $order->items()->createMany($lineItems);

            return $order;
        });

        return redirect()->route('nbk.orders.show', $order)->with('success', 'Memo order NBK dijana.');
    }

    public function show(Request $request, NbkOrder $nbkOrder): View
    {
        abort_unless($nbkOrder->project_id === $request->user()->currentProject()?->id, 403);

        $nbkOrder->load(['items', 'createdBy', 'paidBy', 'purchase']);

        return view('nbk.orders.show', [
            'order' => $nbkOrder,
        ]);
    }

    public function edit(Request $request, NbkOrder $nbkOrder): View
    {
        abort_unless($nbkOrder->project_id === $request->user()->currentProject()?->id, 403);
        abort_if($nbkOrder->isPaid(), 422, 'Order yang sudah dibayar tidak boleh diedit.');

        $project = $request->user()->currentProject();

        $products = $project->nbkProducts()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $nbkOrder->load('items');

        return view('nbk.orders.create', [
            'project' => $project,
            'products' => $products,
            'order' => $nbkOrder,
            'qtyByProductId' => $nbkOrder->items->pluck('qty_ordered', 'nbk_product_id')->all(),
        ]);
    }

    public function update(Request $request, NbkOrder $nbkOrder): RedirectResponse
    {
        abort_unless($nbkOrder->project_id === $request->user()->currentProject()?->id, 403);
        abort_if($nbkOrder->isPaid(), 422, 'Order yang sudah dibayar tidak boleh diedit.');

        $project = $request->user()->currentProject();
        $validated = $this->validateItems($request);

        DB::transaction(function () use ($validated, $project, $nbkOrder) {
            [$lineItems, $totalBuy, $totalSell] = $this->buildLineItems($validated['items'], $project);

            abort_if(empty($lineItems), 422, 'Tiada produk dengan kuantiti order.');

            $nbkOrder->items()->delete();
            $nbkOrder->items()->createMany($lineItems);

            $nbkOrder->update([
                'order_date' => $validated['order_date'],
                'total_buy' => $totalBuy,
                'total_sell' => $totalSell,
                'total_profit' => $totalSell - $totalBuy,
            ]);
        });

        return redirect()->route('nbk.orders.show', $nbkOrder)->with('success', 'Memo order NBK dikemaskini.');
    }

    public function destroy(Request $request, NbkOrder $nbkOrder): RedirectResponse
    {
        abort_unless($nbkOrder->project_id === $request->user()->currentProject()?->id, 403);
        abort_if($nbkOrder->isPaid(), 422, 'Order yang sudah dibayar tidak boleh dipadam.');

        $nbkOrder->delete();

        return redirect()->route('nbk.orders.index')->with('success', 'Memo order NBK dipadam.');
    }

    public function markPaid(Request $request, NbkOrder $nbkOrder): RedirectResponse
    {
        abort_unless($nbkOrder->project_id === $request->user()->currentProject()?->id, 403);
        abort_unless($request->user()->hasFullAccess(), 403, 'Hanya owner/superuser boleh tandai order NBK dibayar.');
        abort_if($nbkOrder->isPaid(), 422, 'Order ini sudah ditandai dibayar.');

        $request->validate([
            'receipt' => ['nullable', 'file', 'max:8192', new ValidReceiptFile],
        ]);

        $receiptPath = null;

        if ($request->hasFile('receipt')) {
            $receiptPath = $this->storeReceipt($request->file('receipt'), 'receipts/'.$nbkOrder->project_id);
        }

        DB::transaction(function () use ($nbkOrder, $request, $receiptPath) {
            $purchase = Purchase::create([
                'project_id' => $nbkOrder->project_id,
                'recorded_by' => $request->user()->id,
                'category' => Purchase::CATEGORY_BAHAN_MENTAH,
                'purchase_date' => now()->toDateString(),
                'supplier_name' => 'NBK - Nasi Berlauk Kelantan',
                'description' => 'Belian NBK (Memo #'.$nbkOrder->id.')',
                'amount' => $nbkOrder->total_buy,
                'receipt_path' => $receiptPath,
                'notes' => $nbkOrder->items->count().' produk, memo #'.$nbkOrder->id,
            ]);

            $nbkOrder->update([
                'paid_at' => now(),
                'paid_by' => $request->user()->id,
                'purchase_id' => $purchase->id,
            ]);
        });

        return back()->with('success', 'Order ditandai dibayar dan direkodkan dalam Belian.');
    }

    /** @return array{order_date: string, items: array} */
    private function validateItems(Request $request): array
    {
        return $request->validate([
            'order_date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.nbk_product_id' => ['required', 'exists:nbk_products,id'],
            'items.*.qty_ordered' => ['required', 'integer', 'min:0'],
        ]);
    }

    /** @return array{0: array, 1: float, 2: float} [lineItems, totalBuy, totalSell] */
    private function buildLineItems(array $items, Project $project): array
    {
        $totalBuy = 0;
        $totalSell = 0;
        $lineItems = [];

        foreach ($items as $item) {
            if ((int) $item['qty_ordered'] <= 0) {
                continue;
            }

            $product = NbkProduct::where('project_id', $project->id)->findOrFail($item['nbk_product_id']);

            if (! $product->is_active) {
                continue;
            }

            $qty = (int) $item['qty_ordered'];
            $sellPrice = (float) ($product->sell_price ?? 0);

            $buyTotal = $product->unit_cost * $qty;
            $sellTotal = $sellPrice * $qty;

            $totalBuy += $buyTotal;
            $totalSell += $sellTotal;

            $lineItems[] = [
                'nbk_product_id' => $product->id,
                'product_name' => $product->name,
                'unit_cost' => $product->unit_cost,
                'qty_ordered' => $qty,
                'sell_price' => $sellPrice,
                'buy_total' => $buyTotal,
                'sell_total' => $sellTotal,
                'profit' => $sellTotal - $buyTotal,
            ];
        }

        return [$lineItems, $totalBuy, $totalSell];
    }
}
