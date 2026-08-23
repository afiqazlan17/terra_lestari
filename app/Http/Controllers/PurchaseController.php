<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $project = $request->user()->currentProject();

        $purchases = $project->purchases()
            ->with('recordedBy')
            ->latest('purchase_date')
            ->latest('id')
            ->paginate(20);

        return view('purchases.index', [
            'project' => $project,
            'purchases' => $purchases,
        ]);
    }

    public function create(Request $request): View
    {
        return view('purchases.create', [
            'project' => $request->user()->currentProject(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $project = $request->user()->currentProject();

        $validated = $request->validate([
            'purchase_date' => ['required', 'date'],
            'category' => ['required', Rule::in(array_keys(Purchase::CATEGORIES))],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'receipt' => ['nullable', 'image', 'max:8192'],
        ]);

        $receiptPath = null;

        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('receipts/'.$project->id, 'public');
        }

        $project->purchases()->create([
            'recorded_by' => $request->user()->id,
            'category' => $validated['category'],
            'purchase_date' => $validated['purchase_date'],
            'supplier_name' => $validated['supplier_name'] ?? null,
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'receipt_path' => $receiptPath,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('purchases.index')->with('success', 'Belian berjaya direkodkan.');
    }

    public function destroy(Request $request, Purchase $purchase): RedirectResponse
    {
        abort_unless($purchase->project_id === $request->user()->currentProject()?->id, 403);

        if ($purchase->receipt_path) {
            Storage::disk('public')->delete($purchase->receipt_path);
        }

        $purchase->delete();

        return back()->with('success', 'Belian dipadam.');
    }
}
