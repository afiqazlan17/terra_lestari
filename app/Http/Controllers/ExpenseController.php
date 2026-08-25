<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $project = $request->user()->currentProject();

        $expenses = $project->purchases()
            ->where('category', '!=', Purchase::CATEGORY_BAHAN_MENTAH)
            ->with('recordedBy')
            ->latest('purchase_date')
            ->latest('id')
            ->paginate(20);

        return view('expenses.index', [
            'project' => $project,
            'expenses' => $expenses,
        ]);
    }

    public function create(Request $request): View
    {
        return view('expenses.create', [
            'project' => $request->user()->currentProject(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $project = $request->user()->currentProject();

        $validated = $request->validate([
            'purchase_date' => ['required', 'date'],
            'category' => ['required', Rule::in(array_keys(Purchase::EXPENSE_CATEGORIES))],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'receipt' => ['nullable', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
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

        return redirect()->route('expenses.index')->with('success', 'Perbelanjaan berjaya direkodkan.');
    }

    public function destroy(Request $request, Purchase $expense): RedirectResponse
    {
        abort_unless($expense->project_id === $request->user()->currentProject()?->id, 403);

        if ($expense->receipt_path) {
            Storage::disk('public')->delete($expense->receipt_path);
        }

        $expense->delete();

        return back()->with('success', 'Perbelanjaan dipadam.');
    }
}
