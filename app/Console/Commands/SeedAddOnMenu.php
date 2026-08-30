<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\Project;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:seed-add-on-menu')]
#[Description('One-off: creates the "Add-On" category and its variable-price items (Nasi Putih, Ayam Cincang, Daging Berlengas, Sotong Goreng Tepung). Safe to re-run.')]
class SeedAddOnMenu extends Command
{
    private const ITEMS = [
        'Nasi Putih',
        'Ayam Cincang',
        'Daging Berlengas',
        'Sotong Goreng Tepung',
    ];

    public function handle(): void
    {
        $project = Project::where('is_active', true)->orderBy('id')->first();

        if (! $project) {
            $this->error('No active project found.');

            return;
        }

        $category = Category::firstOrCreate(
            ['project_id' => $project->id, 'name' => 'Add-On'],
            ['sku_prefix' => 'AD', 'sort_order' => $project->categories()->count()]
        );

        if (! $category->sku_prefix) {
            $category->update(['sku_prefix' => 'AD']);
        }

        foreach (self::ITEMS as $index => $name) {
            $product = Product::firstOrCreate(
                ['project_id' => $project->id, 'category_id' => $category->id, 'name' => $name],
                [
                    'sku' => 'AD'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'price' => 0,
                    'is_variable_price' => true,
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );

            $this->info($product->wasRecentlyCreated ? "Created: {$name}" : "Already exists: {$name}");
        }
    }
}
