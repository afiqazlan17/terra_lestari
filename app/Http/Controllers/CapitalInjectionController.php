<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\StoresReceipts;
use App\Models\CapitalInjection;
use App\Rules\ValidReceiptFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class CapitalInjectionController extends Controller
{
    use StoresReceipts;

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasFullAccess(), 403);

        $project = $request->user()->currentProject();

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'injected_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'receipt' => ['nullable', 'file', 'max:8192', new ValidReceiptFile],
        ]);

        $receiptPath = null;

        if ($request->hasFile('receipt')) {
            $receiptPath = $this->storeReceipt($request->file('receipt'), 'capital-injections/'.$project->id);
        }

        $project->capitalInjections()->create([
            'recorded_by' => $request->user()->id,
            'amount' => $validated['amount'],
            'injected_at' => $validated['injected_at'],
            'source_account' => 'Terra Lestari OCBC',
            'notes' => $validated['notes'] ?? null,
            'receipt_path' => $receiptPath,
        ]);

        return back()->with('success', 'Modal awal berjaya direkodkan.');
    }

    public function update(Request $request, CapitalInjection $capitalInjection): RedirectResponse
    {
        abort_unless($request->user()->hasFullAccess(), 403);
        abort_unless($capitalInjection->project_id === $request->user()->currentProject()?->id, 403);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'injected_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $changes = [];

        if ((float) $capitalInjection->amount !== (float) $validated['amount']) {
            $changes[] = sprintf(
                'Jumlah: RM %s → RM %s',
                number_format($capitalInjection->amount, 2),
                number_format($validated['amount'], 2)
            );
        }

        $newDate = Carbon::parse($validated['injected_at']);

        if (! $capitalInjection->injected_at->isSameDay($newDate)) {
            $changes[] = sprintf(
                'Tarikh: %s → %s',
                $capitalInjection->injected_at->format('d M Y'),
                $newDate->format('d M Y')
            );
        }

        $oldNotes = $capitalInjection->notes ?? '';
        $newNotes = $validated['notes'] ?? '';

        if ($oldNotes !== $newNotes) {
            $changes[] = sprintf(
                'Nota: "%s" → "%s"',
                $oldNotes !== '' ? $oldNotes : '(kosong)',
                $newNotes !== '' ? $newNotes : '(kosong)'
            );
        }

        if (! empty($changes)) {
            $capitalInjection->edits()->create([
                'edited_by' => $request->user()->id,
                'changes' => implode("\n", $changes),
            ]);

            $capitalInjection->update([
                'amount' => $validated['amount'],
                'injected_at' => $validated['injected_at'],
                'notes' => $validated['notes'] ?? null,
            ]);
        }

        return back()->with('success', 'Rekod modal dikemaskini.');
    }

    public function updateReceipt(Request $request, CapitalInjection $capitalInjection): RedirectResponse
    {
        abort_unless($request->user()->hasFullAccess(), 403);
        abort_unless($capitalInjection->project_id === $request->user()->currentProject()?->id, 403);

        $validated = $request->validate([
            'receipt' => ['nullable', 'file', 'max:8192', new ValidReceiptFile],
            'remove_receipt' => ['nullable', 'boolean'],
        ]);

        if ($capitalInjection->receipt_path && ($request->hasFile('receipt') || $request->boolean('remove_receipt'))) {
            Storage::disk('public')->delete($capitalInjection->receipt_path);
        }

        if ($request->hasFile('receipt')) {
            $capitalInjection->update([
                'receipt_path' => $this->storeReceipt($request->file('receipt'), 'capital-injections/'.$capitalInjection->project_id),
            ]);
        } elseif ($request->boolean('remove_receipt')) {
            $capitalInjection->update(['receipt_path' => null]);
        }

        return back()->with('success', 'Lampiran dikemaskini.');
    }

    public function destroy(Request $request, CapitalInjection $capitalInjection): RedirectResponse
    {
        abort_unless($request->user()->hasFullAccess(), 403);
        abort_unless($capitalInjection->project_id === $request->user()->currentProject()?->id, 403);

        if ($capitalInjection->receipt_path) {
            Storage::disk('public')->delete($capitalInjection->receipt_path);
        }

        $capitalInjection->delete();

        return back()->with('success', 'Rekod modal dipadam.');
    }
}
