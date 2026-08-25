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
        self::TYPE_DINE_IN => 'Dine In',
        self::TYPE_TAKEAWAY => 'Take Away',
    ];

    public const PAYMENT_METHOD_CASH = 'cash';
    public const PAYMENT_METHOD_QR = 'qr';
    public const PAYMENT_METHOD_CARD = 'card';

    public const PAYMENT_METHODS = [
        self::PAYMENT_METHOD_CASH => 'Tunai',
        self::PAYMENT_METHOD_QR => 'QR / DuitNow',
        self::PAYMENT_METHOD_CARD => 'Kad Debit/Kad Kredit',
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

    public function paymentMethodLabel(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? $this->payment_method;
    }
}
