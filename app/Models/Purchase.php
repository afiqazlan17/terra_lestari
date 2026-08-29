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
    public const CATEGORY_RENOVASI = 'renovasi';
    public const CATEGORY_LAIN = 'lain_lain';

    public const CATEGORIES = [
        self::CATEGORY_BAHAN_MENTAH => 'Bahan Mentah',
        self::CATEGORY_SEWA => 'Sewa',
        self::CATEGORY_UTILITI => 'Utiliti',
        self::CATEGORY_GAJI => 'Gaji',
        self::CATEGORY_RENOVASI => 'Renovasi',
        self::CATEGORY_LAIN => 'Lain-lain',
    ];

    /** Categories shown on the Perbelanjaan (non food cost) page. */
    public const EXPENSE_CATEGORIES = [
        self::CATEGORY_SEWA => 'Sewa',
        self::CATEGORY_UTILITI => 'Utiliti',
        self::CATEGORY_GAJI => 'Gaji',
        self::CATEGORY_RENOVASI => 'Renovasi',
        self::CATEGORY_LAIN => 'Lain-lain',
    ];

    protected $fillable = [
        'project_id', 'recorded_by', 'category', 'purchase_date', 'supplier_name',
        'description', 'amount', 'receipt_path', 'notes',
        'void_reason', 'voided_at', 'voided_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'amount' => 'decimal:2',
        'voided_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /**
     * Filename used when this purchase's receipt is backed up to Google
     * Drive, e.g. "260814 - RM750.00 - MSBENJAMIN - BAHAN MENTAH - Ayam.pdf".
     */
    public function driveBackupFileName(string $extension): string
    {
        $supplier = $this->supplier_name
            ? strtoupper(preg_replace('/\s+/', '', $this->supplier_name))
            : 'TUNAI';

        $parts = [
            $this->purchase_date->format('ymd'),
            'RM'.number_format((float) $this->amount, 2),
            $supplier,
            strtoupper($this->categoryLabel()),
            $this->description,
        ];

        $name = implode(' - ', $parts).'.'.($extension ?: 'bin');

        return preg_replace('/[\/\\\\:*?"<>|]/', '', $name);
    }
}
