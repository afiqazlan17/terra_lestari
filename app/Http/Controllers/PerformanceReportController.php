<?php

namespace App\Http\Controllers;

use App\Models\CapitalInjection;
use App\Models\Category;
use App\Models\DailySession;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * One-page business performance report for any date range - sales, costs,
 * profit, best sellers, trends, capital and cash control - for presenting
 * to partners. Dates/hours are grouped in PHP rather than with DATE()/
 * HOUR(), since those differ between MySQL (production) and SQLite (local).
 */
class PerformanceReportController extends Controller
{
    private const ADJUSTMENT_PREFIX = 'Pelarasan Jualan';

    public function show(Request $request): View
    {
        $project = $request->user()->currentProject();

        abort_if(! $project, 404, 'Tiada projek/outlet dijumpai.');

        $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->input('to'))->endOfDay() : now()->endOfDay();
        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $orders = Order::where('project_id', $project->id)
            ->where('status', Order::STATUS_COMPLETED)
            ->whereBetween('created_at', [$from, $to])
            ->get(['id', 'created_at', 'payment_method', 'order_type', 'total']);

        $items = OrderItem::whereIn('order_id', $orders->pluck('id'))
            ->get(['order_id', 'product_id', 'product_name', 'qty', 'subtotal']);

        $adjustmentOrderIds = $items->filter(fn ($i) => str_starts_with($i->product_name, self::ADJUSTMENT_PREFIX))
            ->pluck('order_id')->unique()->flip();
        $menuItems = $items->reject(fn ($i) => str_starts_with($i->product_name, self::ADJUSTMENT_PREFIX));

        $totalSales = (float) $orders->sum('total');
        $adjustmentSales = (float) $orders->filter(fn ($o) => $adjustmentOrderIds->has($o->id))->sum('total');
        $posOrders = $orders->reject(fn ($o) => $adjustmentOrderIds->has($o->id));

        $dailySales = $orders->groupBy(fn ($o) => $o->created_at->toDateString())->map(fn ($g) => (float) $g->sum('total'));
        $operatingDays = $dailySales->filter(fn ($v) => $v > 0)->count();
        $calendarDays = (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;

        $purchases = Purchase::where('project_id', $project->id)
            ->whereNull('voided_at')
            ->whereBetween('purchase_date', [$from->toDateString(), $to->toDateString()])
            ->get(['category', 'supplier_name', 'amount']);

        $costByCategory = collect(Purchase::CATEGORIES)
            ->map(fn ($label, $key) => ['label' => $label, 'amount' => (float) $purchases->where('category', $key)->sum('amount')])
            ->filter(fn ($row) => $row['amount'] > 0);

        $foodCost = (float) $purchases->where('category', Purchase::CATEGORY_BAHAN_MENTAH)->sum('amount');
        $renovation = (float) $purchases->where('category', Purchase::CATEGORY_RENOVASI)->sum('amount');
        $totalCost = (float) $purchases->sum('amount');

        return view('reports.performance', [
            'project' => $project,
            'from' => $from,
            'to' => $to,
            'calendarDays' => $calendarDays,
            'operatingDays' => $operatingDays,

            'totalSales' => $totalSales,
            'orderCount' => $posOrders->count(),
            'avgOrder' => $posOrders->count() ? (float) $posOrders->sum('total') / $posOrders->count() : 0,
            'avgPerDay' => $operatingDays ? $totalSales / $operatingDays : 0,
            'itemsSold' => (int) $menuItems->sum('qty'),
            'adjustmentSales' => $adjustmentSales,

            'foodCost' => $foodCost,
            'renovation' => $renovation,
            'totalCost' => $totalCost,
            'costByCategory' => $costByCategory,
            'grossProfit' => $totalSales - $foodCost,
            'netProfit' => $totalSales - $totalCost,
            'foodCostPct' => $totalSales > 0 ? $foodCost / $totalSales * 100 : null,
            'menuMargin' => $this->menuMargin($menuItems),

            'byPayment' => $this->shareBy($orders, 'payment_method', Order::PAYMENT_METHODS),
            'byOrderType' => $this->shareBy($posOrders, 'order_type', Order::TYPES),
            'bySource' => $this->salesBySource($project->id, $menuItems),

            'dailyTrend' => $this->dailyTrend($from, $to, $dailySales),
            'weekly' => $this->weekly($from, $to, $dailySales),
            'byWeekday' => $this->byWeekday($dailySales),
            'byHour' => $this->byHour($posOrders),

            'topByQty' => $this->topItems($menuItems, 'qty'),
            'topByRevenue' => $this->topItems($menuItems, 'subtotal'),
            'byCategory' => $this->salesByCategory($menuItems),

            'topSuppliers' => $purchases->groupBy(fn ($p) => $p->supplier_name ?: '(Tiada nama)')
                ->map(fn ($g, $name) => ['name' => $name, 'amount' => (float) $g->sum('amount'), 'count' => $g->count()])
                ->sortByDesc('amount')->take(8)->values(),

            'capitalInRange' => CapitalInjection::where('project_id', $project->id)
                ->whereBetween('injected_at', [$from->toDateString(), $to->toDateString()])
                ->orderBy('injected_at')->get(),
            'capitalAllTime' => (float) CapitalInjection::where('project_id', $project->id)->sum('amount'),

            'cashControl' => $this->cashControl($project->id, $from, $to, $orders),
            'voided' => Order::where('project_id', $project->id)
                ->where('status', Order::STATUS_VOIDED)
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw('COUNT(*) as n, COALESCE(SUM(total), 0) as amount')
                ->first(),
        ]);
    }

    /** Menu revenue minus cost price, for line items whose product has a cost set. */
    private function menuMargin(Collection $menuItems): ?array
    {
        $costs = Product::whereIn('id', $menuItems->pluck('product_id')->filter()->unique())
            ->whereNotNull('cost')->pluck('cost', 'id');

        $costed = $menuItems->filter(fn ($i) => $i->product_id && $costs->has($i->product_id));
        if ($costed->isEmpty()) {
            return null;
        }

        $revenue = (float) $costed->sum('subtotal');
        $cost = (float) $costed->sum(fn ($i) => (float) $costs[$i->product_id] * $i->qty);

        return [
            'revenue' => $revenue,
            'margin' => $revenue - $cost,
            'pct' => $revenue > 0 ? ($revenue - $cost) / $revenue * 100 : 0,
            'coverage' => $menuItems->sum('subtotal') > 0 ? $revenue / (float) $menuItems->sum('subtotal') * 100 : 0,
        ];
    }

    private function shareBy(Collection $orders, string $field, array $labels): Collection
    {
        $total = (float) $orders->sum('total');

        return $orders->groupBy($field)
            ->map(fn ($g, $key) => [
                'label' => $labels[$key] ?? ($key ?: 'Lain-lain'),
                'amount' => (float) $g->sum('total'),
                'count' => $g->count(),
                'pct' => $total > 0 ? (float) $g->sum('total') / $total * 100 : 0,
            ])
            ->sortByDesc('amount')->values();
    }

    private function salesBySource(int $projectId, Collection $menuItems): array
    {
        $nbkCategoryIds = Category::where('project_id', $projectId)->where('is_nbk', true)->pluck('id');
        $nbkProductIds = Product::whereIn('category_id', $nbkCategoryIds)->pluck('id')->flip();

        $nbk = (float) $menuItems->filter(fn ($i) => $i->product_id && $nbkProductIds->has($i->product_id))->sum('subtotal');
        $total = (float) $menuItems->sum('subtotal');

        return [
            ['label' => 'Sajian Baginda (SB)', 'amount' => $total - $nbk, 'pct' => $total > 0 ? ($total - $nbk) / $total * 100 : 0],
            ['label' => 'Fresh From Kelantan (NBK)', 'amount' => $nbk, 'pct' => $total > 0 ? $nbk / $total * 100 : 0],
        ];
    }

    private function dailyTrend(Carbon $from, Carbon $to, Collection $dailySales): Collection
    {
        $days = collect();
        for ($d = $from->copy()->startOfDay(); $d->lte($to); $d->addDay()) {
            $days->push(['date' => $d->copy(), 'total' => $dailySales[$d->toDateString()] ?? 0.0]);
        }

        return $days;
    }

    /** Monday-start weeks, clipped to the report range. */
    private function weekly(Carbon $from, Carbon $to, Collection $dailySales): Collection
    {
        $weeks = collect();
        for ($start = $from->copy()->startOfDay(); $start->lte($to); $start = $end->copy()->addDay()) {
            $end = $start->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay()->min($to->copy()->startOfDay());
            $total = 0.0;
            $open = 0;
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $v = $dailySales[$d->toDateString()] ?? 0.0;
                $total += $v;
                $open += $v > 0 ? 1 : 0;
            }
            $weeks->push(['start' => $start->copy(), 'end' => $end->copy(), 'total' => $total, 'days' => $open]);
        }

        return $weeks;
    }

    /** Average sales per operating day for each weekday (Monday first). */
    private function byWeekday(Collection $dailySales): Collection
    {
        $open = $dailySales->filter(fn ($v) => $v > 0);

        return collect(range(1, 7))->map(function ($iso) use ($open) {
            $days = $open->filter(fn ($v, $date) => Carbon::parse($date)->dayOfWeekIso === $iso);

            return [
                'label' => Carbon::now()->startOfWeek(Carbon::MONDAY)->addDays($iso - 1)->translatedFormat('l'),
                'avg' => $days->count() ? (float) $days->sum() / $days->count() : 0.0,
                'days' => $days->count(),
            ];
        });
    }

    private function byHour(Collection $posOrders): Collection
    {
        $hours = $posOrders->groupBy(fn ($o) => (int) $o->created_at->format('G'))
            ->map(fn ($g) => ['total' => (float) $g->sum('total'), 'count' => $g->count()]);

        if ($hours->isEmpty()) {
            return collect();
        }

        return collect(range($hours->keys()->min(), $hours->keys()->max()))
            ->map(fn ($h) => ['hour' => $h] + ($hours[$h] ?? ['total' => 0.0, 'count' => 0]));
    }

    private function topItems(Collection $menuItems, string $by): Collection
    {
        return $menuItems->groupBy('product_name')
            ->map(fn ($g, $name) => ['name' => $name, 'qty' => (int) $g->sum('qty'), 'revenue' => (float) $g->sum('subtotal')])
            ->sortByDesc($by === 'qty' ? 'qty' : 'revenue')
            ->take(10)->values();
    }

    private function salesByCategory(Collection $menuItems): Collection
    {
        $categoryOf = Product::whereIn('id', $menuItems->pluck('product_id')->filter()->unique())
            ->with('category:id,name')->get(['id', 'category_id'])
            ->mapWithKeys(fn ($p) => [$p->id => $p->category?->name ?? 'Lain-lain']);

        return $menuItems->groupBy(fn ($i) => $categoryOf[$i->product_id] ?? 'Lain-lain')
            ->map(fn ($g, $name) => ['name' => $name, 'revenue' => (float) $g->sum('subtotal'), 'qty' => (int) $g->sum('qty')])
            ->sortByDesc('revenue')->values();
    }

    /** Tutup Hari counted vs POS-expected, summed over the range. */
    private function cashControl(int $projectId, Carbon $from, Carbon $to, Collection $orders): array
    {
        $sessions = DailySession::where('project_id', $projectId)
            ->where('status', 'closed')
            ->whereBetween('opened_at', [$from, $to])
            ->get();

        $byDateMethod = $orders->groupBy(fn ($o) => $o->created_at->toDateString().'|'.$o->payment_method)
            ->map(fn ($g) => (float) $g->sum('total'));

        $cashDiff = 0.0;
        $qrDiff = 0.0;
        foreach ($sessions->groupBy(fn ($s) => $s->opened_at->toDateString()) as $date => $daySessions) {
            $cashDiff += (float) $daySessions->sum('closing_cash') - (float) $daySessions->sum('opening_cash')
                - ($byDateMethod[$date.'|'.Order::PAYMENT_METHOD_CASH] ?? 0);
            $qrDiff += (float) $daySessions->sum('closing_qr') - ($byDateMethod[$date.'|'.Order::PAYMENT_METHOD_QR] ?? 0);
        }

        return [
            'closedDays' => $sessions->pluck('opened_at')->map->toDateString()->unique()->count(),
            'cashDiff' => $cashDiff,
            'qrDiff' => $qrDiff,
        ];
    }
}
