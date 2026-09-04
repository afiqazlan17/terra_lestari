<?php

namespace App\Http\Controllers;

use App\Models\DailySession;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Project;
use App\Services\DailySalesReportService;
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

        return view('daily-sessions.reports-index', [
            'sessions' => $sessions,
        ]);
    }
}
