<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NbkProduct extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_OUT_OF_STOCK = 'out_of_stock';

    public const STATUS_EXCLUDED = 'excluded';

    public const STATUSES = [
        self::STATUS_ACTIVE => 'Aktif',
        self::STATUS_OUT_OF_STOCK => 'Tiada Stok',
        self::STATUS_EXCLUDED => 'Excluded',
    ];

    protected $fillable = [
        'project_id', 'name', 'unit_cost', 'min_qty', 'sell_price', 'status', 'sort_order',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'sell_price' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function isOrderable(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
