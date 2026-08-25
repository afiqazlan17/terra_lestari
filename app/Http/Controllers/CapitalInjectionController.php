<?php

namespace App\Http\Controllers;

use App\Models\CapitalInjection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CapitalInjectionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasFullAccess(), 403);

        $project = $request->user()->currentProject();

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'injected_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'receipt' => ['nullable', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
        ]);

        $receiptPath = null;

        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('capital-injections/'.$project->id, 'public');
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

    public function updateReceipt(Request $request, CapitalInjection $capitalInjection): RedirectResponse
    {
        abort_unless($request->user()->hasFullAccess(), 403);
        abort_unless($capitalInjection->project_id === $request->user()->currentProject()?->id, 403);

        $validated = $request->validate([
            'receipt' => ['nullable', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
            'remove_receipt' => ['nullable', 'boolean'],
        ]);

        if ($capitalInjection->receipt_path && ($request->hasFile('receipt') || $request->boolean('remove_receipt'))) {
            Storage::disk('public')->delete($capitalInjection->receipt_path);
        }

        if ($request->hasFile('receipt')) {
            $capitalInjection->update([
                'receipt_path' => $request->file('receipt')->store('capital-injections/'.$capitalInjection->project_id, 'public'),
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
