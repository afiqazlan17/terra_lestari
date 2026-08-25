<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CapitalInjection extends Model
{
    protected $fillable = [
        'project_id', 'recorded_by', 'amount', 'source_account', 'injected_at', 'notes', 'receipt_path',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'injected_at' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function edits(): HasMany
    {
        return $this->hasMany(CapitalInjectionEdit::class)->latest();
    }
}
