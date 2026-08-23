<?php

namespace App\Http\Controllers;

use App\Models\CapitalInjection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CapitalInjectionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasFullAccess(), 403);

        $project = $request->user()->currentProject();

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'injected_at' => ['required', 'date'],
            'source_account' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $project->capitalInjections()->create([
            'recorded_by' => $request->user()->id,
            'amount' => $validated['amount'],
            'injected_at' => $validated['injected_at'],
            'source_account' => $validated['source_account'] ?? 'Terra Lestari OCBC',
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Modal awal berjaya direkodkan.');
    }

    public function destroy(Request $request, CapitalInjection $capitalInjection): RedirectResponse
    {
        abort_unless($request->user()->hasFullAccess(), 403);
        abort_unless($capitalInjection->project_id === $request->user()->currentProject()?->id, 403);

        $capitalInjection->delete();

        return back()->with('success', 'Rekod modal dipadam.');
    }
}
