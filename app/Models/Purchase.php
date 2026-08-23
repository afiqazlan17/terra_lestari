<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    public const CATEGORY_BAHAN_MENTAH = 'bahan_mentah';
    public const CATEGORY_SEWA = 'sewa';
    public const CATEGORY_UTILITI = 'utiliti';
    public const CATEGORY_GAJI = 'gaji';
    public const CATEGORY_LAIN = 'lain_lain';

    public const CATEGORIES = [
        self::CATEGORY_BAHAN_MENTAH => 'Bahan Mentah',
        self::CATEGORY_SEWA => 'Sewa',
        self::CATEGORY_UTILITI => 'Utiliti',
        self::CATEGORY_GAJI => 'Gaji',
        self::CATEGORY_LAIN => 'Lain-lain',
    ];

    protected $fillable = [
        'project_id', 'recorded_by', 'category', 'purchase_date', 'supplier_name',
        'description', 'amount', 'receipt_path', 'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }
}
