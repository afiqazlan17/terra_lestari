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
use Illuminate\Support\Facades\DB;

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
     * Form to retroactively fill in Tutup Hari figures for any past date
     * that has no closed session - whether Buka Hari was never pressed that
     * day, or it was opened and never closed. Every row is optional, since
     * some of those dates are days the shop was genuinely shut.
     */
    public function backfillForm(Request $request, SalesSummaryService $summaryService): View
    {
        $project = $request->user()->currentProject();

        abort_if(! $project, 404, 'Tiada projek/outlet dijumpai.');

        $rows = collect($this->backfillCandidatesFor($project))->map(function ($item) use ($project, $summaryService) {
            $summary = $summaryService->summaryFor($project, $item['date'], $item['date']);

            return $item + [
                'cashSales' => $summary['cashSales'],
                'qrSales' => $summary['qrSales'],
                'priorClosingCash' => $this->closingCashBefore($project, $item['date']),
            ];
        });

        return view('daily-sessions.backfill', ['rows' => $rows]);
    }

    public function backfillStore(Request $request): RedirectResponse
    {
        $project = $request->user()->currentProject();

        abort_if(! $project, 404, 'Tiada projek/outlet dijumpai.');

        $validated = $request->validate([
            'entries' => ['required', 'array'],
            'entries.*.opening_cash' => ['nullable', 'numeric', 'min:0'],
            'entries.*.closing_cash' => ['nullable', 'numeric', 'min:0', 'required_with:entries.*.closing_qr'],
            'entries.*.closing_qr' => ['nullable', 'numeric', 'min:0', 'required_with:entries.*.closing_cash'],
        ], [
            'entries.*.closing_cash.required_with' => 'Isi Tunai Akhir sekali untuk hari yang ada QR diisi.',
            'entries.*.closing_qr.required_with' => 'Isi QR sekali untuk hari yang ada Tunai Akhir diisi.',
        ]);

        $note = DailySession::BACKFILL_NOTE.' oleh '.$request->user()->name.' pada '.now()->translatedFormat('d F Y, H:i').']';
        $saved = 0;

        DB::transaction(function () use ($project, $validated, $request, $note, &$saved) {
            // Re-derived server-side (not from the submitted keys) so a
            // tampered request can't create sessions for arbitrary dates.
            foreach ($this->backfillCandidatesFor($project) as $item) {
                $entry = $validated['entries'][$item['date']->toDateString()] ?? null;

                if (! $entry || ! isset($entry['closing_cash'], $entry['closing_qr'])) {
                    continue;
                }

                $closing = [
                    'closed_by' => $request->user()->id,
                    'closed_at' => $item['date']->copy()->endOfDay(),
                    'closing_cash' => $entry['closing_cash'],
                    'closing_qr' => $entry['closing_qr'],
                    'status' => 'closed',
                ];

                if ($item['openSession']) {
                    $item['openSession']->update($closing + [
                        'notes' => trim(($item['openSession']->notes ? $item['openSession']->notes.' ' : '').$note),
                    ]);
                } else {
                    // Blank opening cash carries over the cash the previous
                    // closed day ended with - which includes rows saved
                    // earlier in this same loop, since they're in the DB now.
                    DailySession::create($closing + [
                        'project_id' => $project->id,
                        'opened_by' => $request->user()->id,
                        'opened_at' => $item['date']->copy()->startOfDay(),
                        'opening_cash' => $entry['opening_cash'] ?? $this->closingCashBefore($project, $item['date']) ?? 0,
                        'notes' => $note,
                    ]);
                }

                $saved++;
            }
        });

        if ($saved === 0) {
            return back()->withInput()->with('error', 'Tiada hari diisi. Isi Tunai Akhir dan QR untuk sekurang-kurangnya satu hari.');
        }

        // Tutup Hari figures alone don't add to Jualan/Cash Book - those
        // count orders - so the days' sales still need recording.
        if ($request->user()->isSuperuser()) {
            return redirect()->route('sales-adjustments.create')
                ->with('success', "{$saved} hari berjaya diisi. Sahkan jualan hari-hari tu di bawah supaya masuk dalam Jualan & Cash Book.");
        }

        return redirect()->route('daily-session.reports.index')
            ->with('success', "{$saved} hari berjaya diisi. Minta Afiq/Amirul sahkan jualan hari-hari tu di Pelarasan Jualan supaya masuk dalam Jualan & Cash Book.");
    }

    private function staleOpenSessionFor(Project $project): ?DailySession
    {
        return DailySession::where('project_id', $project->id)
            ->where('status', 'open')
            ->whereDate('opened_at', '<', now()->toDateString())
            ->latest('opened_at')
            ->first();
    }

    /**
     * Past dates (within the last 60 days, never before the first-ever
     * session) with no closed session, oldest first. 'openSession' is set
     * when Buka Hari was pressed that day but Tutup Hari never was.
     *
     * @return array<int, array{date: Carbon, openSession: ?DailySession}>
     */
    private function backfillCandidatesFor(Project $project): array
    {
        $sessions = DailySession::where('project_id', $project->id)->get();

        if ($sessions->isEmpty()) {
            return [];
        }

        $closedDates = $sessions->where('status', 'closed')
            ->map(fn ($s) => $s->opened_at->toDateString())
            ->flip();
        $openByDate = $sessions->where('status', 'open')
            ->keyBy(fn ($s) => $s->opened_at->toDateString());

        $start = $sessions->pluck('opened_at')->min()->copy()->startOfDay()
            ->max(now()->subDays(60)->startOfDay());
        $end = now()->subDay()->startOfDay();

        $candidates = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $key = $date->toDateString();

            if (! $closedDates->has($key)) {
                $candidates[] = ['date' => $date->copy(), 'openSession' => $openByDate->get($key)];
            }
        }

        return $candidates;
    }

    private function closingCashBefore(Project $project, Carbon $date): ?float
    {
        $prior = DailySession::where('project_id', $project->id)
            ->where('status', 'closed')
            ->whereDate('opened_at', '<', $date->toDateString())
            ->latest('opened_at')
            ->first();

        return $prior ? (float) $prior->closing_cash : null;
    }
}
