<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Project;
use Illuminate\Support\Carbon;

class SalesSummaryService
{
    /**
     * Sales summary for completed orders between $from and $to (inclusive, by calendar date).
     */
    public function summaryFor(Project $project, Carbon $from, Carbon $to): array
    {
        $baseQuery = fn () => Order::where('project_id', $project->id)
            ->where('status', Order::STATUS_COMPLETED)
            ->whereDate('created_at', '>=', $from->toDateString())
            ->whereDate('created_at', '<=', $to->toDateString());

        $totalSales = (float) $baseQuery()->sum('total');
        $orderCount = $baseQuery()->count();

        $byPaymentMethod = $baseQuery()
            ->selectRaw('payment_method, SUM(total) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method')
            ->map(fn ($v) => (float) $v);

        $byOrderType = $baseQuery()
            ->selectRaw('order_type, SUM(total) as total')
            ->groupBy('order_type')
            ->pluck('total', 'order_type')
            ->map(fn ($v) => (float) $v);

        $orderIds = $baseQuery()->pluck('id');

        $itemCount = (int) OrderItem::whereIn('order_id', $orderIds)->sum('qty');

        $nbkCategoryIds = Category::where('project_id', $project->id)->where('is_nbk', true)->pluck('id');
        $nbkProductIds = Product::whereIn('category_id', $nbkCategoryIds)->pluck('id');

        $nbkSales = (float) OrderItem::whereIn('order_id', $orderIds)
            ->whereIn('product_id', $nbkProductIds)
            ->sum('subtotal');

        $topItems = OrderItem::whereIn('order_id', $orderIds)
            ->selectRaw('product_name, SUM(qty) as qty_sold')
            ->groupBy('product_name')
            ->orderByDesc('qty_sold')
            ->limit(5)
            ->get();

        $voidOrders = Order::where('project_id', $project->id)
            ->where('status', Order::STATUS_VOIDED)
            ->whereDate('created_at', '>=', $from->toDateString())
            ->whereDate('created_at', '<=', $to->toDateString())
            ->get();

        return [
            'totalSales' => $totalSales,
            'orderCount' => $orderCount,
            'itemCount' => $itemCount,
            'cashSales' => (float) ($byPaymentMethod[Order::PAYMENT_METHOD_CASH] ?? 0),
            'qrSales' => (float) ($byPaymentMethod[Order::PAYMENT_METHOD_QR] ?? 0),
            'cardSales' => (float) ($byPaymentMethod[Order::PAYMENT_METHOD_CARD] ?? 0),
            'dineInSales' => (float) ($byOrderType[Order::TYPE_DINE_IN] ?? 0),
            'takeawaySales' => (float) ($byOrderType[Order::TYPE_TAKEAWAY] ?? 0),
            'sbSales' => $totalSales - $nbkSales,
            'nbkSales' => $nbkSales,
            'topItems' => $topItems,
            'voidOrders' => $voidOrders,
        ];
    }

    /**
     * Cash tally for a single calendar date: opening cash of session(s) opened
     * that date, plus cash sales that date, compared against closing cash if
     * the session has been closed. Null when no session was opened that date.
     */
    public function cashTallyFor(Project $project, Carbon $date, float $cashSales): ?array
    {
        $sessions = \App\Models\DailySession::where('project_id', $project->id)
            ->whereDate('opened_at', $date->toDateString())
            ->get();

        if ($sessions->isEmpty()) {
            return null;
        }

        $openingCash = (float) $sessions->sum('opening_cash');
        $jangkaan = $openingCash + $cashSales;

        $closedSessions = $sessions->where('status', 'closed');
        $allClosed = $closedSessions->count() === $sessions->count();

        return [
            'openingCash' => $openingCash,
            'jangkaan' => $jangkaan,
            'sebenar' => $allClosed ? (float) $closedSessions->sum('closing_cash') : null,
            'beza' => $allClosed ? (float) $closedSessions->sum('closing_cash') - $jangkaan : null,
        ];
    }
}
