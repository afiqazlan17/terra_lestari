<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\StoresReceipts;
use App\Http\Controllers\Concerns\SyncsDriveBackupFolder;
use App\Models\Purchase;
use App\Rules\ValidReceiptFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    use StoresReceipts, SyncsDriveBackupFolder;

    public function index(Request $request): View
    {
        $project = $request->user()->currentProject();

        $purchases = $project->purchases()
            ->where('category', Purchase::CATEGORY_BAHAN_MENTAH)
            ->with(['recordedBy', 'voidedBy', 'edits.editedBy'])
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
            'category' => Purchase::CATEGORY_BAHAN_MENTAH,
            'purchase_date' => $validated['purchase_date'],
            'supplier_name' => $validated['supplier_name'] ?? null,
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'receipt_path' => $receiptPath,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('purchases.index')->with('success', 'Belian berjaya direkodkan.');
    }

    public function update(Request $request, Purchase $purchase): RedirectResponse
    {
        abort_unless($purchase->project_id === $request->user()->currentProject()?->id, 403);
        abort_unless($request->user()->hasFullAccess(), 403, 'Hanya owner/superuser boleh edit rekod belian.');
        abort_if($purchase->isVoided(), 422, 'Rekod yang telah di-void tidak boleh diedit.');

        $validated = $request->validate([
            'purchase_date' => ['required', 'date'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'receipt' => ['nullable', 'file', 'max:8192', new ValidReceiptFile],
        ]);

        $changes = [];
        $receiptPath = null;

        if ($request->hasFile('receipt') && ! $purchase->receipt_path) {
            $receiptPath = $this->storeReceipt($request->file('receipt'), 'receipts/'.$purchase->project_id);
            $changes[] = 'Resit: ditambah';
        }

        if ((float) $purchase->amount !== (float) $validated['amount']) {
            $changes[] = sprintf('Jumlah: RM %s → RM %s', number_format($purchase->amount, 2), number_format($validated['amount'], 2));
        }

        $newDate = Carbon::parse($validated['purchase_date']);
        $dateChanged = ! $purchase->purchase_date->isSameDay($newDate);
        if ($dateChanged) {
            $changes[] = sprintf('Tarikh: %s → %s', $purchase->purchase_date->format('d F Y'), $newDate->format('d F Y'));
        }

        if (($purchase->supplier_name ?? '') !== ($validated['supplier_name'] ?? '')) {
            $changes[] = sprintf('Pembekal: "%s" → "%s"', $purchase->supplier_name ?: '(kosong)', $validated['supplier_name'] ?: '(kosong)');
        }

        if ($purchase->description !== $validated['description']) {
            $changes[] = sprintf('Keterangan: "%s" → "%s"', $purchase->description, $validated['description']);
        }

        if (($purchase->notes ?? '') !== ($validated['notes'] ?? '')) {
            $changes[] = sprintf('Nota: "%s" → "%s"', $purchase->notes ?: '(kosong)', $validated['notes'] ?: '(kosong)');
        }

        $driveWarning = null;

        if (! empty($changes)) {
            $purchase->edits()->create([
                'edited_by' => $request->user()->id,
                'changes' => implode("\n", $changes),
            ]);

            $purchase->update([
                'purchase_date' => $validated['purchase_date'],
                'supplier_name' => $validated['supplier_name'] ?? null,
                'description' => $validated['description'],
                'amount' => $validated['amount'],
                'notes' => $validated['notes'] ?? null,
                ...($receiptPath ? ['receipt_path' => $receiptPath] : []),
            ]);

            $driveWarning = $this->syncDriveFileAfterEdit($purchase, $dateChanged);
        }

        $redirect = back()->with('success', 'Belian dikemaskini.');

        return $driveWarning ? $redirect->with('error', $driveWarning) : $redirect;
    }

    public function void(Request $request, Purchase $purchase): RedirectResponse
    {
        abort_unless($purchase->project_id === $request->user()->currentProject()?->id, 403);
        abort_unless($request->user()->hasFullAccess(), 403, 'Hanya owner/superuser boleh void rekod belian.');
        abort_if($purchase->isVoided(), 422, 'Rekod ini sudah di-void.');

        $validated = $request->validate([
            'void_reason' => ['required', 'string', 'max:255'],
        ]);

        $purchase->update([
            'void_reason' => $validated['void_reason'],
            'voided_at' => now(),
            'voided_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Belian di-void.');
    }
}
