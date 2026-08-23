<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const TYPE_DINE_IN = 'dine_in';
    public const TYPE_TAKEAWAY = 'takeaway';

    public const TYPES = [
        self::TYPE_DINE_IN => 'Makan Sini',
        self::TYPE_TAKEAWAY => 'Bungkus',
    ];

    protected $fillable = [
        'project_id', 'daily_session_id', 'cashier_id', 'order_number',
        'subtotal', 'discount', 'total', 'payment_method', 'order_type', 'status',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function dailySession(): BelongsTo
    {
        return $this->belongsTo(DailySession::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->order_type] ?? $this->order_type;
    }
}
