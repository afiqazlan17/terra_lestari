<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    public const PAPER_WIDTH_58MM = '58mm';
    public const PAPER_WIDTH_80MM = '80mm';

    protected $fillable = ['company_id', 'name', 'slug', 'address', 'phone', 'is_active', 'receipt_paper_width'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function dailySessions(): HasMany
    {
        return $this->hasMany(DailySession::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function capitalInjections(): HasMany
    {
        return $this->hasMany(CapitalInjection::class);
    }

    public function nbkProducts(): HasMany
    {
        return $this->hasMany(NbkProduct::class);
    }

    public function nbkOrders(): HasMany
    {
        return $this->hasMany(NbkOrder::class);
    }
}
