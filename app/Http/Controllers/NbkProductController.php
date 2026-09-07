<?php

namespace App\Http\Controllers;

use App\Models\NbkProduct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NbkProductController extends Controller
{
    public function index(Request $request): View
    {
        $project = $request->user()->currentProject();

        $products = $project->nbkProducts()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('nbk.products.index', [
            'project' => $project,
            'products' => $products,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $project = $request->user()->currentProject();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'min_qty' => ['required', 'integer', 'min:1'],
            'sell_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $project->nbkProducts()->create([
            'name' => $validated['name'],
            'unit_cost' => $validated['unit_cost'],
            'min_qty' => $validated['min_qty'],
            'sell_price' => $validated['sell_price'] ?? null,
            'status' => NbkProduct::STATUS_ACTIVE,
            'sort_order' => $project->nbkProducts()->count(),
        ]);

        return back()->with('success', 'Produk NBK ditambah.');
    }

    /**
     * Invoice-matching only considers "active" products, so a catalog entry
     * left as "Tiada Stok"/"Excluded" silently drops that line off any
     * scanned invoice instead of matching it - this is the bulk fix for
     * when the catalog has drifted out of sync with what's actually
     * orderable.
     */
    public function activateAll(Request $request): RedirectResponse
    {
        $project = $request->user()->currentProject();

        $count = $project->nbkProducts()
            ->where('status', '!=', NbkProduct::STATUS_ACTIVE)
            ->update(['status' => NbkProduct::STATUS_ACTIVE]);

        return back()->with('success', "{$count} produk NBK ditukar ke status Aktif.");
    }

    public function update(Request $request, NbkProduct $nbkProduct): RedirectResponse
    {
        abort_unless($nbkProduct->project_id === $request->user()->currentProject()?->id, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'min_qty' => ['required', 'integer', 'min:1'],
            'sell_price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:'.implode(',', array_keys(NbkProduct::STATUSES))],
        ]);

        $nbkProduct->update([
            'name' => $validated['name'],
            'unit_cost' => $validated['unit_cost'],
            'min_qty' => $validated['min_qty'],
            'sell_price' => $validated['sell_price'] ?? null,
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Produk NBK dikemaskini.');
    }

    public function destroy(Request $request, NbkProduct $nbkProduct): RedirectResponse
    {
        abort_unless($nbkProduct->project_id === $request->user()->currentProject()?->id, 403);

        $nbkProduct->delete();

        return back()->with('success', 'Produk NBK dipadam.');
    }
}
