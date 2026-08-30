<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NbkProduct extends Model
{
    protected $fillable = [
        'project_id', 'name', 'unit_cost', 'min_qty', 'sell_price', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
