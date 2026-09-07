<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\StoresReceipts;
use App\Models\NbkOrder;
use App\Models\NbkProduct;
use App\Models\Project;
use App\Models\Purchase;
use App\Rules\ValidReceiptFile;
use App\Services\NbkBakiService;
use App\Services\NbkInvoiceExtractionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NbkOrderController extends Controller
{
    use StoresReceipts;

    public function landing(Request $request, NbkBakiService $bakiService): View
    {
        $project = $request->user()->currentProject();

        return view('nbk.index', [
            'baki' => $project ? $bakiService->compute($project, Carbon::today()) : null,
        ]);
    }

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

            $invoicePath = $validated['invoice_path'] ?? null;

            // The path is client-supplied (set by our own JS from the earlier
            // extract-invoice response), so only trust it if it actually
            // points at this project's own invoice folder.
            if ($invoicePath !== null && ! str_starts_with($invoicePath, 'nbk-invoices/'.$project->id.'/')) {
                $invoicePath = null;
            }

            $order = $project->nbkOrders()->create([
                'created_by' => $request->user()->id,
                'order_date' => Carbon::parse($validated['invoice_date'])->addDay()->toDateString(),
                'invoice_path' => $invoicePath,
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
                'order_date' => Carbon::parse($validated['invoice_date'])->addDay()->toDateString(),
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
            // order_date is the day AFTER the invoice (stock ordered today
            // sells tomorrow) - the Belian record should reflect the
            // invoice's own date, so step back a day to recover it.
            $purchase = Purchase::create([
                'project_id' => $nbkOrder->project_id,
                'recorded_by' => $request->user()->id,
                'category' => Purchase::CATEGORY_BAHAN_MENTAH,
                'purchase_date' => $nbkOrder->order_date->copy()->subDay()->toDateString(),
                'supplier_name' => 'NBK - Nasi Berlauk Kelantan',
                'description' => 'Belian NBK (Memo #'.$nbkOrder->displayNumber().')',
                'amount' => $nbkOrder->total_buy,
                'receipt_path' => $receiptPath,
                'notes' => $nbkOrder->items->count().' produk, memo #'.$nbkOrder->displayNumber(),
            ]);

            $nbkOrder->update([
                'paid_at' => now(),
                'paid_by' => $request->user()->id,
                'purchase_id' => $purchase->id,
            ]);
        });

        return back()->with('success', 'Order ditandai dibayar dan direkodkan dalam Belian.');
    }

    public function extractInvoice(Request $request, NbkInvoiceExtractionService $service): JsonResponse
    {
        $request->validate([
            'invoice' => ['required', 'file', 'max:8192', new ValidReceiptFile],
        ]);

        $project = $request->user()->currentProject();

        $products = $project->nbkProducts()
            ->where('status', NbkProduct::STATUS_ACTIVE)
            ->get();

        try {
            $result = $service->extract($request->file('invoice'));
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['error' => 'Gagal baca invois. Sila isi manual.'], 422);
        }

        // Keep the invoice file itself attached to the order, so staff can
        // pull it up later without digging through their phone/WhatsApp.
        $invoicePath = $this->storeReceipt($request->file('invoice'), 'nbk-invoices/'.$project->id);

        $matched = [];
        $unmatched = [];
        $priceUpdates = 0;

        foreach ($result['items'] as $line) {
            $name = trim((string) ($line['name'] ?? ''));
            $qty = (int) ($line['qty'] ?? 0);
            $invoicePrice = is_numeric($line['price'] ?? null) ? round((float) $line['price'], 2) : null;

            if ($name === '' || $qty <= 0) {
                continue;
            }

            $product = $this->matchProduct($name, $products);

            if ($product) {
                // The invoice is the source of truth for price - NBK's own
                // prices change over time and our catalog can drift stale,
                // so sync it to whatever this invoice actually printed.
                if ($invoicePrice !== null && $invoicePrice > 0 && round((float) $product->unit_cost, 2) !== $invoicePrice) {
                    $product->update(['unit_cost' => $invoicePrice]);
                    $priceUpdates++;
                }

                $matched[] = ['nbk_product_id' => $product->id, 'name' => $product->name, 'qty' => $qty, 'unit_cost' => (float) $product->unit_cost];
            } else {
                $unmatched[] = ['name' => $name, 'qty' => $qty];
            }
        }

        // The invoice's own date tells us when NBK was ordered; the stock it
        // carries is for the day after (staff receive/sell it the next day).
        // Fall back to today's date if the AI couldn't read one off the image.
        $invoiceDate = $this->parseInvoiceDate($result['invoice_date']) ?? now()->startOfDay();
        $orderDate = $invoiceDate->copy()->addDay();

        return response()->json([
            'matched' => $matched,
            'unmatched' => $unmatched,
            'invoice_date' => $invoiceDate->toDateString(),
            'order_date' => $orderDate->toDateString(),
            'price_updates' => $priceUpdates,
            'invoice_path' => $invoicePath,
        ]);
    }

    private function parseInvoiceDate(?string $value): ?\Illuminate\Support\Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Fuzzy-match an invoice line's printed name against the active NBK
     * catalog, so small AI misreads (or the invoice using an abbreviated
     * name) still land on the right product - same spirit as the supplier
     * "did you mean" matching used for Belian/Perbelanjaan.
     */
    private function matchProduct(string $name, Collection $products): ?NbkProduct
    {
        $normalize = fn (string $s) => strtolower(trim(preg_replace('/[^a-z0-9\s]/i', '', $s)));
        $target = $normalize($name);

        if ($target === '') {
            return null;
        }

        $best = null;
        $bestScore = 0;

        foreach ($products as $product) {
            similar_text($target, $normalize($product->name), $percent);

            if ($percent > $bestScore) {
                $bestScore = $percent;
                $best = $product;
            }
        }

        return $bestScore >= 55 ? $best : null;
    }

    /** @return array{invoice_date: string, invoice_path: ?string, items: array} */
    private function validateItems(Request $request): array
    {
        return $request->validate([
            'invoice_date' => ['required', 'date'],
            'invoice_path' => ['nullable', 'string'],
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

            if (! $product->isOrderable()) {
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
