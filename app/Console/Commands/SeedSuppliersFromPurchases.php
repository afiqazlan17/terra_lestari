<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:seed-suppliers-from-purchases')]
#[Description('One-off: backfills the suppliers list from distinct supplier_name values already recorded on Purchase/Expense rows, so the Pembekal autocomplete has a running start. Safe to re-run.')]
class SeedSuppliersFromPurchases extends Command
{
    public function handle(): int
    {
        $projects = Project::all();
        $total = 0;

        foreach ($projects as $project) {
            $names = Purchase::where('project_id', $project->id)
                ->whereNotNull('supplier_name')
                ->pluck('supplier_name')
                ->map(fn ($name) => trim($name))
                ->filter(fn ($name) => $name !== '')
                ->unique(fn ($name) => mb_strtolower($name))
                ->values();

            foreach ($names as $name) {
                $supplier = Supplier::firstOrCreate([
                    'project_id' => $project->id,
                    'name' => $name,
                ]);

                if ($supplier->wasRecentlyCreated) {
                    $total++;
                    $this->info("{$project->name}: \"{$name}\"");
                }
            }
        }

        $this->info("Selesai. {$total} pembekal baru ditambah.");

        return self::SUCCESS;
    }
}
