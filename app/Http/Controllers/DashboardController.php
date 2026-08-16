<?php

namespace App\Http\Controllers;

use App\Models\DailySession;
use App\Models\Order;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $project = $request->user()->currentProject();

        abort_if(! $project, 404, 'Tiada projek/outlet dijumpai.');

        $today = now()->toDateString();
        $weekStart = now()->startOfWeek()->toDateString();

        $todaySales = Order::where('project_id', $project->id)
            ->where('status', 'completed')
            ->whereDate('created_at', $today)
            ->sum('total');

        $todayPurchases = Purchase::where('project_id', $project->id)
            ->whereDate('purchase_date', $today)
            ->sum('amount');

        $weekSales = Order::where('project_id', $project->id)
            ->where('status', 'completed')
            ->whereDate('created_at', '>=', $weekStart)
            ->sum('total');

        $weekPurchases = Purchase::where('project_id', $project->id)
            ->whereDate('purchase_date', '>=', $weekStart)
            ->sum('amount');

        $dailyTrend = Order::where('project_id', $project->id)
            ->where('status', 'completed')
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

        $recentPurchases = Purchase::where('project_id', $project->id)
            ->with('recordedBy')
            ->latest('purchase_date')
            ->limit(5)
            ->get();

        return view('dashboard', [
            'project' => $project,
            'todaySales' => $todaySales,
            'todayPurchases' => $todayPurchases,
            'todayProfit' => $todaySales - $todayPurchases,
            'weekSales' => $weekSales,
            'weekPurchases' => $weekPurchases,
            'weekProfit' => $weekSales - $weekPurchases,
            'dailyTrend' => $dailyTrend,
            'currentSession' => $currentSession,
            'recentPurchases' => $recentPurchases,
        ]);
    }
}
