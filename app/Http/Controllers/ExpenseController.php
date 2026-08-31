<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\StoresReceipts;
use App\Http\Controllers\Concerns\SyncsDriveBackupFolder;
use App\Models\Purchase;
use App\Rules\ValidReceiptFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    use StoresReceipts, SyncsDriveBackupFolder;

    public function index(Request $request): View
    {
        $project = $request->user()->currentProject();

        $expenses = $project->purchases()
            ->where('category', '!=', Purchase::CATEGORY_BAHAN_MENTAH)
            ->with(['recordedBy', 'voidedBy', 'edits.editedBy'])
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
            'receipt' => ['nullable', 'file', 'max:8192', new ValidReceiptFile],
        ]);

        $receiptPath = null;

        if ($request->hasFile('receipt')) {
            $receiptPath = $this->storeReceipt($request->file('receipt'), 'receipts/'.$project->id);
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

    public function update(Request $request, Purchase $expense): RedirectResponse
    {
        abort_unless($expense->project_id === $request->user()->currentProject()?->id, 403);
        abort_unless($request->user()->hasFullAccess(), 403, 'Hanya owner/superuser boleh edit rekod perbelanjaan.');
        abort_if($expense->isVoided(), 422, 'Rekod yang telah di-void tidak boleh diedit.');

        $validated = $request->validate([
            'purchase_date' => ['required', 'date'],
            'category' => ['required', Rule::in(array_keys(Purchase::EXPENSE_CATEGORIES))],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'receipt' => ['nullable', 'file', 'max:8192', new ValidReceiptFile],
        ]);

        $changes = [];
        $receiptPath = null;

        if ($request->hasFile('receipt') && ! $expense->receipt_path) {
            $receiptPath = $this->storeReceipt($request->file('receipt'), 'receipts/'.$expense->project_id);
            $changes[] = 'Resit: ditambah';
        }

        if ((float) $expense->amount !== (float) $validated['amount']) {
            $changes[] = sprintf('Jumlah: RM %s → RM %s', number_format($expense->amount, 2), number_format($validated['amount'], 2));
        }

        $newDate = Carbon::parse($validated['purchase_date']);
        $dateChanged = ! $expense->purchase_date->isSameDay($newDate);
        if ($dateChanged) {
            $changes[] = sprintf('Tarikh: %s → %s', $expense->purchase_date->format('d F Y'), $newDate->format('d F Y'));
        }

        if ($expense->category !== $validated['category']) {
            $changes[] = sprintf('Kategori: %s → %s', $expense->categoryLabel(), Purchase::CATEGORIES[$validated['category']] ?? $validated['category']);
        }

        if (($expense->supplier_name ?? '') !== ($validated['supplier_name'] ?? '')) {
            $changes[] = sprintf('Pembekal/Penerima: "%s" → "%s"', $expense->supplier_name ?: '(kosong)', $validated['supplier_name'] ?: '(kosong)');
        }

        if ($expense->description !== $validated['description']) {
            $changes[] = sprintf('Keterangan: "%s" → "%s"', $expense->description, $validated['description']);
        }

        if (($expense->notes ?? '') !== ($validated['notes'] ?? '')) {
            $changes[] = sprintf('Nota: "%s" → "%s"', $expense->notes ?: '(kosong)', $validated['notes'] ?: '(kosong)');
        }

        if (! empty($changes)) {
            $expense->edits()->create([
                'edited_by' => $request->user()->id,
                'changes' => implode("\n", $changes),
            ]);

            $expense->update([
                'purchase_date' => $validated['purchase_date'],
                'category' => $validated['category'],
                'supplier_name' => $validated['supplier_name'] ?? null,
                'description' => $validated['description'],
                'amount' => $validated['amount'],
                'notes' => $validated['notes'] ?? null,
                ...($receiptPath ? ['receipt_path' => $receiptPath] : []),
            ]);

            $driveWarning = $this->syncDriveFileAfterEdit($expense, $dateChanged);
        }

        $redirect = back()->with('success', 'Perbelanjaan dikemaskini.');

        return isset($driveWarning) && $driveWarning ? $redirect->with('error', $driveWarning) : $redirect;
    }

    public function void(Request $request, Purchase $expense): RedirectResponse
    {
        abort_unless($expense->project_id === $request->user()->currentProject()?->id, 403);
        abort_unless($request->user()->hasFullAccess(), 403, 'Hanya owner/superuser boleh void rekod perbelanjaan.');
        abort_if($expense->isVoided(), 422, 'Rekod ini sudah di-void.');

        $validated = $request->validate([
            'void_reason' => ['required', 'string', 'max:255'],
        ]);

        $expense->update([
            'void_reason' => $validated['void_reason'],
            'voided_at' => now(),
            'voided_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Perbelanjaan di-void.');
    }
}
