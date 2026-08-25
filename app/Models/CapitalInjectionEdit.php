<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CapitalInjectionEdit extends Model
{
    protected $fillable = [
        'capital_injection_id', 'edited_by', 'changes',
    ];

    public function capitalInjection(): BelongsTo
    {
        return $this->belongsTo(CapitalInjection::class);
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}
