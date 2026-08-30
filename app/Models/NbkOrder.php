<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NbkOrder extends Model
{
    protected $fillable = [
        'project_id', 'created_by', 'order_date', 'total_buy', 'total_sell', 'total_profit',
        'paid_at', 'paid_by', 'purchase_id',
    ];

    protected $casts = [
        'order_date' => 'date',
        'total_buy' => 'decimal:2',
        'total_sell' => 'decimal:2',
        'total_profit' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(NbkOrderItem::class);
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    /**
     * The order's position among all orders currently existing for its
     * project - unlike the raw database id, this has no gaps left behind
     * by deleted draft memos.
     */
    public function displayNumber(): int
    {
        return static::where('project_id', $this->project_id)
            ->where('id', '<=', $this->id)
            ->count();
    }
}
