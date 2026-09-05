<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Project;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:seed-nasi-campur')]
#[Description('Creates the "Nasi Campur" menu category as a 6-column square price grid (RM6.00-RM15.00, RM0.50 steps) - each product is named after the category so the POS shows price-only tiles. Safe to re-run.')]
class SeedNasiCampurCategory extends Command
{
    public function handle(): int
    {
        $project = Project::where('is_active', true)->orderBy('id')->first();

        if (! $project) {
            $this->error('No active project found.');

            return self::FAILURE;
        }

        $category = Category::firstOrCreate(
            ['project_id' => $project->id, 'name' => 'Nasi Campur'],
            ['sort_order' => $project->categories()->count()]
        );

        $category->update(['tile_shape' => 'square', 'grid_columns' => 6]);

        $created = 0;

        for ($cents = 600; $cents <= 1500; $cents += 50) {
            $price = $cents / 100;

            $product = $category->products()->firstOrCreate(
                ['project_id' => $project->id, 'name' => 'Nasi Campur', 'price' => $price],
                ['is_active' => true, 'is_variable_price' => false, 'sort_order' => 0]
            );

            if ($product->wasRecentlyCreated) {
                $created++;
            }
        }

        $this->info("Kategori \"Nasi Campur\": square, 6 lajur. {$created} produk baru dicipta (RM6.00 - RM15.00).");

        return self::SUCCESS;
    }
}
