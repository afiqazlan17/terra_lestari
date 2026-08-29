<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $project = $request->user()->currentProject();

        $categories = $project->categories()
            ->with(['products' => fn ($q) => $q->orderBy('price')->orderBy('name')])
            ->orderBy('sort_order')
            ->get();

        return view('products.index', [
            'project' => $project,
            'categories' => $categories,
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $project = $request->user()->currentProject();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $project->categories()->create([
            'name' => $validated['name'],
            'sort_order' => $project->categories()->count(),
        ]);

        return back()->with('success', 'Kategori ditambah.');
    }

    public function store(Request $request): RedirectResponse
    {
        $project = $request->user()->currentProject();

        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:64', Rule::unique('products')->where(fn ($q) => $q->where('project_id', $project->id))],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $project->products()->create([
            'category_id' => $validated['category_id'] ?? null,
            'name' => $validated['name'],
            'sku' => $validated['sku'] ?? null,
            'price' => $validated['price'],
            'cost' => $validated['cost'] ?? null,
            'is_active' => true,
            'sort_order' => $project->products()->count(),
        ]);

        return back()->with('success', 'Menu ditambah.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->project_id === $request->user()->currentProject()?->id, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:64', Rule::unique('products')->where(fn ($q) => $q->where('project_id', $product->project_id))->ignore($product->id)],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $product->update([
            'name' => $validated['name'],
            'sku' => $validated['sku'] ?? null,
            'price' => $validated['price'],
            'cost' => $validated['cost'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Menu dikemaskini.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->project_id === $request->user()->currentProject()?->id, 403);

        $product->delete();

        return back()->with('success', 'Menu dipadam.');
    }
}
