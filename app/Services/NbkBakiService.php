<?php

namespace App\Services;

use App\Models\NbkOrderItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Project;
use Illuminate\Support\Carbon;

class NbkBakiService
{
    /**
     * Compares what was ordered from NBK that day against what actually sold,
     * grouped into Nasi / Kuih (not per-dish - the POS side collapsed Kuih
     * into generic price-tier tiles, so per-dish reconciliation isn't
     * recoverable; Nasi/Kuih is the finest grain both sides agree on).
     *
     * "Ordered" comes from nbk_order_items (the detailed purchase catalog,
     * classified Nasi vs Kuih by name prefix - the catalog has no explicit
     * type column, but every item is named "Nasi ..." or "Kuih ..."). "Sold"
     * comes from POS order_items via the matching product category. Null
     * when nothing was ordered from NBK that day (nothing to reconcile).
     */
    public function compute(Project $project, Carbon $date): ?array
    {
        $orderedRows = NbkOrderItem::whereHas('order', function ($query) use ($project, $date) {
            $query->where('project_id', $project->id)->whereDate('order_date', $date->toDateString());
        })->get(['product_name', 'qty_ordered']);

        if ($orderedRows->isEmpty()) {
            return null;
        }

        $orderedNasi = 0;
        $orderedKuih = 0;

        foreach ($orderedRows as $row) {
            if (str_starts_with(trim($row->product_name), 'Nasi')) {
                $orderedNasi += $row->qty_ordered;
            } else {
                $orderedKuih += $row->qty_ordered;
            }
        }

        $orderIds = Order::where('project_id', $project->id)
            ->where('status', Order::STATUS_COMPLETED)
            ->whereDate('created_at', $date->toDateString())
            ->pluck('id');

        $soldByCategory = OrderItem::whereIn('order_id', $orderIds)
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereIn('categories.name', ['Nasi (Fresh From Kelantan)', 'Kuih (Fresh From Kelantan)'])
            ->selectRaw('categories.name as category, SUM(order_items.qty) as qty')
            ->groupBy('categories.name')
            ->pluck('qty', 'category');

        $soldNasi = (int) ($soldByCategory['Nasi (Fresh From Kelantan)'] ?? 0);
        $soldKuih = (int) ($soldByCategory['Kuih (Fresh From Kelantan)'] ?? 0);

        return [
            'nasi' => ['ordered' => $orderedNasi, 'sold' => $soldNasi, 'baki' => max($orderedNasi - $soldNasi, 0)],
            'kuih' => ['ordered' => $orderedKuih, 'sold' => $soldKuih, 'baki' => max($orderedKuih - $soldKuih, 0)],
        ];
    }
}
