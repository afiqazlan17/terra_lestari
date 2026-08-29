<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['project_id', 'name', 'sku_prefix', 'sort_order'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Next unused SKU for this category (e.g. "SN08" after SN01-SN07),
     * or null if this category has no SKU prefix configured.
     */
    public function nextSku(): ?string
    {
        if (! $this->sku_prefix) {
            return null;
        }

        $max = $this->products()
            ->where('sku', 'like', $this->sku_prefix.'%')
            ->pluck('sku')
            ->map(fn ($sku) => (int) preg_replace('/\D/', '', substr($sku, strlen($this->sku_prefix))))
            ->max();

        return $this->sku_prefix.str_pad((string) (($max ?? 0) + 1), 2, '0', STR_PAD_LEFT);
    }
}
