<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Supplier extends Model
{
    protected $fillable = ['project_id', 'name'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Sorted list of known supplier names for a project, for the
     * autocomplete/suggestion field on Belian and Perbelanjaan forms.
     */
    public static function namesFor(Project $project): array
    {
        return static::where('project_id', $project->id)
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    /**
     * Records a supplier name into the known list if it's new (case-insensitive -
     * relies on the default utf8mb4 collation being case-insensitive, matching
     * the unique index). Blank names are ignored. Safe to call on every
     * Purchase/Expense save so the list grows on its own.
     */
    public static function remember(Project $project, ?string $name): void
    {
        $name = trim((string) $name);

        if ($name === '') {
            return;
        }

        static::firstOrCreate([
            'project_id' => $project->id,
            'name' => $name,
        ]);
    }
}
