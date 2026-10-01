<?php

namespace App\Http\Controllers;

use App\Models\DailySession;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Project;
use App\Services\DailySalesReportService;
use App\Services\NbkBakiService;
use App\Services\SalesSummaryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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
            'closing_qr' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $dailySession->update([
            'closed_by' => $request->user()->id,
            'closed_at' => now(),
            'closing_cash' => $validated['closing_cash'],
            'closing_qr' => $validated['closing_qr'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'closed',
        ]);

        $reportService->sendFor($dailySession);

        return back()
            ->with('success', 'Hari ini telah ditutup. Ringkasan jualan telah dihantar ke emel.')
            ->with('closed_session_id', $dailySession->id);
    }

    public function report(Request $request, DailySession $dailySession, SalesSummaryService $summaryService, NbkBakiService $bakiService): View
    {
        abort_unless($dailySession->project_id === $request->user()->currentProject()?->id, 403);

        $date = $dailySession->opened_at->copy()->startOfDay();
        $summary = $summaryService->summaryFor($dailySession->project, $date, $date);
        $cashTally = $summaryService->cashTallyFor($dailySession->project, $date, $summary['cashSales']);
        $qrTally = $summaryService->qrTallyFor($dailySession->project, $date, $summary['qrSales']);

        return view('daily-sessions.report', [
            'session' => $dailySession,
            'date' => $date,
            'summary' => $summary,
            'cashTally' => $cashTally,
            'qrTally' => $qrTally,
            'bakiNbk' => $bakiService->compute($dailySession->project, $date),
            'weekTrend' => $this->weekTrendEnding($dailySession->project, $date),
            'categoryBreakdown' => $this->categoryBreakdownFor($dailySession->project, $date),
        ]);
    }

    /**
     * Sales for the 7 days ending on $date (not "today") - a report is a
     * fixed historical record, so reopening one from last week must still
     * show the 7 days leading up to that day, not shift with whenever it
     * happens to be viewed.
     */
    private function weekTrendEnding(Project $project, Carbon $date)
    {
        $salesByDay = Order::where('project_id', $project->id)
            ->where('status', Order::STATUS_COMPLETED)
            ->whereDate('created_at', '>=', $date->copy()->subDays(6)->toDateString())
            ->whereDate('created_at', '<=', $date->toDateString())
            ->selectRaw('DATE(created_at) as day, SUM(total) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range(6, 0))->map(function ($daysAgo) use ($date, $salesByDay) {
            $day = $date->copy()->subDays($daysAgo);

            return [
                'day' => $day->translatedFormat('l'),
                'total' => (float) ($salesByDay[$day->toDateString()] ?? 0),
            ];
        });
    }

    /** Sales that single day, grouped by product category. */
    private function categoryBreakdownFor(Project $project, Carbon $date)
    {
        $orderIds = Order::where('project_id', $project->id)
            ->where('status', Order::STATUS_COMPLETED)
            ->whereDate('created_at', $date->toDateString())
            ->pluck('id');

        return OrderItem::whereIn('order_id', $orderIds)
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->selectRaw("COALESCE(categories.name, 'Lain-lain') as category, SUM(order_items.subtotal) as total")
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();
    }

    public function reportsIndex(Request $request): View
    {
        $project = $request->user()->currentProject();

        abort_if(! $project, 404, 'Tiada projek/outlet dijumpai.');

        $sessions = DailySession::where('project_id', $project->id)
            ->where('status', 'closed')
            ->with(['openedBy', 'closedBy'])
            ->latest('opened_at')
            ->paginate(30);

        // Surfaced here too (not just Dashboard) since this is the page staff
        // land on when they notice a date missing from the report list - the
        // real cause is almost always a forgotten Tutup Hari blocking every
        // day after it from getting its own session.
        $staleOpenSession = $this->staleOpenSessionFor($project);

        return view('daily-sessions.reports-index', [
            'sessions' => $sessions,
            'staleOpenSession' => $staleOpenSession,
        ]);
    }

    /**
     * Form to retroactively fill in closing cash/QR for each day that got
     * swallowed by a forgotten Tutup Hari - one row per missing calendar
     * date, from the stale session's open date through yesterday.
     */
    public function backfillForm(Request $request): View|RedirectResponse
    {
        $project = $request->user()->currentProject();

        abort_if(! $project, 404, 'Tiada projek/outlet dijumpai.');

        $staleSession = $this->staleOpenSessionFor($project);

        if (! $staleSession) {
            return redirect()->route('dashboard')->with('error', 'Tiada sesi lama yang tertunggak untuk diisi.');
        }

        return view('daily-sessions.backfill', [
            'staleSession' => $staleSession,
            'missingDates' => $this->missingDatesFor($staleSession),
        ]);
    }

    public function backfillStore(Request $request): RedirectResponse
    {
        $project = $request->user()->currentProject();

        abort_if(! $project, 404, 'Tiada projek/outlet dijumpai.');

        $staleSession = $this->staleOpenSessionFor($project);

        abort_if(! $staleSession, 404, 'Tiada sesi lama yang tertunggak untuk diisi.');

        // Re-derive the missing dates from the stale session itself rather
        // than trusting whatever date keys the client submitted, so a
        // tampered request can't create backdated sessions for arbitrary
        // dates.
        $missingDates = $this->missingDatesFor($staleSession);

        $validated = $request->validate([
            'entries' => ['required', 'array'],
            'entries.*.closing_cash' => ['required', 'numeric', 'min:0'],
            'entries.*.closing_qr' => ['required', 'numeric', 'min:0'],
        ]);

        foreach ($missingDates as $date) {
            abort_unless(isset($validated['entries'][$date->toDateString()]), 422, 'Sila isi semua hari yang tertunggak.');
        }

        $note = '[Diisi retroaktif oleh '.$request->user()->name.' pada '.now()->translatedFormat('d F Y, H:i').']';
        $openingCash = (float) $staleSession->opening_cash;

        foreach ($missingDates as $index => $date) {
            $entry = $validated['entries'][$date->toDateString()];

            if ($index === 0) {
                $staleSession->update([
                    'closed_by' => $request->user()->id,
                    'closed_at' => $date->copy()->endOfDay(),
                    'closing_cash' => $entry['closing_cash'],
                    'closing_qr' => $entry['closing_qr'],
                    'notes' => trim(($staleSession->notes ? $staleSession->notes.' ' : '').$note),
                    'status' => 'closed',
                ]);
            } else {
                DailySession::create([
                    'project_id' => $project->id,
                    'opened_by' => $request->user()->id,
                    'opened_at' => $date->copy()->startOfDay(),
                    'opening_cash' => $openingCash,
                    'closed_by' => $request->user()->id,
                    'closed_at' => $date->copy()->endOfDay(),
                    'closing_cash' => $entry['closing_cash'],
                    'closing_qr' => $entry['closing_qr'],
                    'notes' => $note,
                    'status' => 'closed',
                ]);
            }

            // Next day's float opens with whatever cash this day closed with.
            $openingCash = (float) $entry['closing_cash'];
        }

        return redirect()->route('daily-session.reports.index')
            ->with('success', 'Semua hari tertunggak telah diisi. Hari ini kini boleh dibuka seperti biasa.');
    }

    private function staleOpenSessionFor(Project $project): ?DailySession
    {
        return DailySession::where('project_id', $project->id)
            ->where('status', 'open')
            ->whereDate('opened_at', '<', now()->toDateString())
            ->latest('opened_at')
            ->first();
    }

    /** @return array<int, Carbon> Chronological list of calendar dates from the stale session's open date through yesterday. */
    private function missingDatesFor(DailySession $staleSession): array
    {
        $start = $staleSession->opened_at->copy()->startOfDay();
        $end = now()->subDay()->startOfDay();

        $dates = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dates[] = $date->copy();
        }

        return $dates;
    }
}
