<?php

namespace App\Http\Controllers;

use App\Models\DailySession;
use App\Services\DailySalesReportService;
use App\Services\SalesSummaryService;
use Illuminate\Contracts\View\View;
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
            return back()->with('error', 'Hari ini sudah dibuka.');
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

        return back()->with('success', 'POS sudah beroperasi. Sila jalankan tanggungjawab dengan amanah.');
    }

    public function close(Request $request, DailySession $dailySession, DailySalesReportService $reportService): RedirectResponse
    {
        abort_unless($dailySession->project_id === $request->user()->currentProject()?->id, 403);
        abort_unless($dailySession->isOpen(), 400, 'Sesi ini sudah ditutup.');

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

        $reportService->sendFor($dailySession);

        return back()
            ->with('success', 'Hari ini telah ditutup. Ringkasan jualan telah dihantar ke emel.')
            ->with('closed_session_id', $dailySession->id);
    }

    public function report(Request $request, DailySession $dailySession, SalesSummaryService $summaryService): View
    {
        abort_unless($dailySession->project_id === $request->user()->currentProject()?->id, 403);

        $date = $dailySession->opened_at->copy()->startOfDay();
        $summary = $summaryService->summaryFor($dailySession->project, $date, $date);
        $cashTally = $summaryService->cashTallyFor($dailySession->project, $date, $summary['cashSales']);

        return view('daily-sessions.report', [
            'session' => $dailySession,
            'date' => $date,
            'summary' => $summary,
            'cashTally' => $cashTally,
        ]);
    }
}
