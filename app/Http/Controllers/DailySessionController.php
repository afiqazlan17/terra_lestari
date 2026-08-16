<?php

namespace App\Http\Controllers;

use App\Models\DailySession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DailySessionController extends Controller
{
    public function open(Request $request): RedirectResponse
    {
        $project = $request->user()->currentProject();

        abort_if(! $project, 404, 'Tiada projek/outlet dijumpai.');

        $existing = DailySession::where('project_id', $project->id)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return back()->with('error', 'Hari ni dah dibuka.');
        }

        $validated = $request->validate([
            'opening_cash' => ['required', 'numeric', 'min:0'],
        ]);

        DailySession::create([
            'project_id' => $project->id,
            'opened_by' => $request->user()->id,
            'opened_at' => now(),
            'opening_cash' => $validated['opening_cash'],
            'status' => 'open',
        ]);

        return back()->with('success', 'Hari ni dah dibuka. Selamat berniaga!');
    }

    public function close(Request $request, DailySession $dailySession): RedirectResponse
    {
        abort_unless($dailySession->project_id === $request->user()->currentProject()?->id, 403);
        abort_unless($dailySession->isOpen(), 400, 'Sesi ni dah ditutup.');

        $validated = $request->validate([
            'closing_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $dailySession->update([
            'closed_by' => $request->user()->id,
            'closed_at' => now(),
            'closing_cash' => $validated['closing_cash'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'closed',
        ]);

        return back()->with('success', 'Hari ni dah ditutup.');
    }
}
