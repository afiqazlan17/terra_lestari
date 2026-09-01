<?php

namespace App\Http\Controllers;

use App\Models\DailySession;
use App\Models\Order;
use App\Services\SalesSummaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, SalesSummaryService $summaryService): View
    {
        $project = $request->user()->currentProject();

        abort_if(! $project, 404, 'Tiada projek/outlet dijumpai.');

        $today = now()->toDateString();

        $from = $request->filled('from') ? Carbon::parse($request->input('from')) : Carbon::parse($today);
        $to = $request->filled('to') ? Carbon::parse($request->input('to')) : Carbon::parse($today);
        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }
        $isSingleDay = $from->toDateString() === $to->toDateString();

        $rangeSummary = $summaryService->summaryFor($project, $from, $to);
        $cashTally = $isSingleDay
            ? $summaryService->cashTallyFor($project, $from, $rangeSummary['cashSales'])
            : null;

        $dailyTrend = Order::where('project_id', $project->id)
            ->where('status', Order::STATUS_COMPLETED)
            ->whereDate('created_at', '>=', now()->subDays(6)->toDateString())
            ->selectRaw('DATE(created_at) as day, SUM(total) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => [
                'day' => Carbon::parse($row->day)->translatedFormat('D'),
                'total' => (float) $row->total,
            ]);

        $currentSession = DailySession::where('project_id', $project->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        $sessionForReport = $isSingleDay
            ? DailySession::where('project_id', $project->id)
                ->whereDate('opened_at', $from->toDateString())
                ->latest('opened_at')
                ->first()
            : null;

        return view('dashboard', [
            'project' => $project,
            'from' => $from,
            'to' => $to,
            'isSingleDay' => $isSingleDay,
            'rangeSummary' => $rangeSummary,
            'cashTally' => $cashTally,
            'dailyTrend' => $dailyTrend,
            'currentSession' => $currentSession,
            'sessionForReport' => $sessionForReport,
        ]);
    }
}
