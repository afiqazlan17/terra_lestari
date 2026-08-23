<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CapitalInjection extends Model
{
    protected $fillable = [
        'project_id', 'recorded_by', 'amount', 'source_account', 'injected_at', 'notes',
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
}
