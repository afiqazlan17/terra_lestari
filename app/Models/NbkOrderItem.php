<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NbkOrderItem extends Model
{
    protected $fillable = [
        'nbk_order_id', 'nbk_product_id', 'product_name', 'unit_cost',
        'qty_ordered', 'sell_price', 'buy_total', 'sell_total', 'profit',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'buy_total' => 'decimal:2',
        'sell_total' => 'decimal:2',
        'profit' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(NbkOrder::class, 'nbk_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(NbkProduct::class, 'nbk_product_id');
    }
}
