<?php

namespace App\Http\Controllers;

use App\Models\CapitalInjection;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class FinanceController extends Controller
{
    private function resolveRange(Request $request): array
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : now()->startOfMonth();

        $to = $request->filled('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : now()->endOfDay();

        return [$from, $to];
    }

    public function index(Request $request): View
    {
        $project = $request->user()->currentProject();
        [$from, $to] = $this->resolveRange($request);

        $revenue = Order::where('project_id', $project->id)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from, $to])
            ->sum('total');

        $purchasesByCategory = Purchase::where('project_id', $project->id)
            ->whereBetween('purchase_date', [$from, $to])
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $foodCost = (float) ($purchasesByCategory[Purchase::CATEGORY_BAHAN_MENTAH] ?? 0);
        $otherExpenses = (float) $purchasesByCategory->except(Purchase::CATEGORY_BAHAN_MENTAH)->sum();
        $totalExpenses = $foodCost + $otherExpenses;

        $grossProfit = $revenue - $foodCost;
        $netProfit = $revenue - $totalExpenses;
        $foodCostPercent = $revenue > 0 ? ($foodCost / $revenue) * 100 : 0;

        $totalCapitalInjected = CapitalInjection::where('project_id', $project->id)
            ->whereBetween('injected_at', [$from, $to])
            ->sum('amount');

        return view('finance.index', [
            'project' => $project,
            'from' => $from,
            'to' => $to,
            'revenue' => $revenue,
            'foodCost' => $foodCost,
            'foodCostPercent' => $foodCostPercent,
            'otherExpenses' => $otherExpenses,
            'totalExpenses' => $totalExpenses,
            'grossProfit' => $grossProfit,
            'netProfit' => $netProfit,
            'purchasesByCategory' => $purchasesByCategory,
            'totalCapitalInjected' => $totalCapitalInjected,
        ]);
    }

    public function sales(Request $request): View
    {
        $project = $request->user()->currentProject();
        [$from, $to] = $this->resolveRange($request);

        $orders = Order::where('project_id', $project->id)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from, $to]);

        $totalSales = (clone $orders)->sum('total');
        $orderCount = (clone $orders)->count();
        $averageOrderValue = $orderCount > 0 ? $totalSales / $orderCount : 0;

        $salesByPaymentMethod = (clone $orders)
            ->selectRaw('payment_method, COUNT(*) as order_count, SUM(total) as total')
            ->groupBy('payment_method')
            ->get();

        $topProducts = OrderItem::whereHas('order', function ($query) use ($project, $from, $to) {
            $query->where('project_id', $project->id)
                ->where('status', 'completed')
                ->whereBetween('created_at', [$from, $to]);
        })
            ->selectRaw('product_name, SUM(qty) as qty_sold, SUM(subtotal) as revenue')
            ->groupBy('product_name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $dailyBreakdown = (clone $orders)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as order_count, SUM(total) as total')
            ->groupBy('day')
            ->orderByDesc('day')
            ->get();

        return view('finance.sales', [
            'project' => $project,
            'from' => $from,
            'to' => $to,
            'totalSales' => $totalSales,
            'orderCount' => $orderCount,
            'averageOrderValue' => $averageOrderValue,
            'salesByPaymentMethod' => $salesByPaymentMethod,
            'topProducts' => $topProducts,
            'dailyBreakdown' => $dailyBreakdown,
        ]);
    }

    public function cashbook(Request $request): View
    {
        $project = $request->user()->currentProject();
        [$from, $to] = $this->resolveRange($request);

        $sales = Order::where('project_id', $project->id)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from, $to])
            ->get()
            ->map(fn ($order) => [
                'date' => $order->created_at,
                'type' => 'masuk',
                'description' => 'Jualan '.$order->order_number,
                'amount' => (float) $order->total,
            ]);

        $capital = CapitalInjection::where('project_id', $project->id)
            ->whereBetween('injected_at', [$from, $to])
            ->get()
            ->map(fn ($injection) => [
                'date' => $injection->injected_at,
                'type' => 'masuk',
                'description' => 'Modal awal ('.$injection->source_account.')',
                'amount' => (float) $injection->amount,
            ]);

        $expenses = Purchase::where('project_id', $project->id)
            ->whereBetween('purchase_date', [$from, $to])
            ->get()
            ->map(fn ($purchase) => [
                'date' => $purchase->purchase_date,
                'type' => 'keluar',
                'description' => $purchase->description.' ('.$purchase->categoryLabel().')',
                'amount' => (float) $purchase->amount,
            ]);

        $entries = $sales->concat($capital)->concat($expenses)
            ->sortByDesc('date')
            ->values();

        $totalMasuk = $sales->sum('amount') + $capital->sum('amount');
        $totalKeluar = $expenses->sum('amount');

        return view('finance.cashbook', [
            'project' => $project,
            'from' => $from,
            'to' => $to,
            'entries' => $entries,
            'totalMasuk' => $totalMasuk,
            'totalKeluar' => $totalKeluar,
            'netCash' => $totalMasuk - $totalKeluar,
        ]);
    }
}
