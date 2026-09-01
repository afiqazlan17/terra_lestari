<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\Project;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:seed-nbk-resale-products')]
#[Description('One-off: creates the "Nasi (Fresh From Kelantan)" and "Kuih (Fresh From Kelantan)" categories, seeds their resold NBK products (cost/price from the paid NBK order), and reorders category sort_order to Set Nasi, Nasi FFK, Kuih FFK, Ala Carte, Add-On. Safe to re-run.')]
class SeedNbkResaleProducts extends Command
{
    /** [name, unit_cost, unit_price] - unit_cost/price already divided down to the per-unit (per pack/piece) level. */
    private const NASI_ITEMS = [
        ['Nasi Kerabu Paloh (Telur Masin)', 4.00, 6.00],
        ['Nasi Dagang Ikan (Kachik)', 4.50, 6.50],
        ['Nasi Dagang Ayam (Kachik)', 4.60, 6.50],
        ['Nasi Dagang Ikan (Air Dingin)', 4.40, 6.50],
        ['Nasi Dagang Ayam (Air Dingin)', 4.50, 6.50],
        ['Nasi Dagang Daging Kerutuk (Air Dingin)', 4.40, 6.50],
        ['Nasi Kerabu Ayam Goreng (Air Dingin)', 4.70, 6.50],
        ['Nasi Kerabu Daging Goreng (Air Dingin)', 5.00, 6.50],
        ['Nasi Kerabu Paloh (Ayam Bakar)', 5.00, 7.00],
        ['Nasi Kerabu Paloh (Daging Bakar)', 5.00, 7.00],
        ['Nasi Kerabu Paloh (Ikan Celup Tepung)', 5.00, 7.00],
    ];

    private const KUIH_ITEMS = [
        ['Kuih Topi (3pcs)', 1.50, 2.00],
        ['Tepung Gomok (5pcs)', 1.90, 2.50],
        ['Akok Biasa 5 biji', 2.00, 2.50],
        ['Akok Pandan 5 biji', 2.00, 2.50],
        ['Kuih Bakar 1 pek', 1.80, 2.50],
        ['Pulut Inti', 1.80, 2.50],
        ['Pulut Pagi Biasa', 1.80, 2.50],
        ['Pulut Pagi Kacang', 1.80, 2.50],
        ['Cekmek (4pcs)', 2.20, 3.00],
        ['Beko Nyor', 2.50, 3.00],
        ['Nasi Impit Kuah Kacang', 2.20, 3.00],
        ['Kuih Peria (4pcs)', 2.20, 3.00],
        ['Pulut Ikan Rebus', 2.50, 3.00],
        ['Lompat Tikam', 3.00, 3.50],
        ['Apam Puri', 2.80, 3.50],
        ['Apam Bakar', 2.80, 3.50],
    ];

    public function handle(): int
    {
        $project = Project::where('is_active', true)->orderBy('id')->first();

        if (! $project) {
            $this->error('No active project found.');

            return self::FAILURE;
        }

        $nasiCategory = Category::firstOrCreate(
            ['project_id' => $project->id, 'name' => 'Nasi (Fresh From Kelantan)'],
            ['sku_prefix' => 'NK', 'sort_order' => 1, 'tile_shape' => 'rectangle', 'color_by_price' => true, 'is_nbk' => true]
        );
        $nasiCategory->update(['sku_prefix' => 'NK', 'tile_shape' => 'rectangle', 'color_by_price' => true, 'is_nbk' => true]);

        $kuihCategory = Category::firstOrCreate(
            ['project_id' => $project->id, 'name' => 'Kuih (Fresh From Kelantan)'],
            ['sku_prefix' => 'KK', 'sort_order' => 2, 'tile_shape' => 'square', 'color_by_price' => true, 'is_nbk' => true]
        );
        $kuihCategory->update(['sku_prefix' => 'KK', 'tile_shape' => 'square', 'color_by_price' => true, 'is_nbk' => true]);

        $this->seedItems($project, $nasiCategory, self::NASI_ITEMS);
        $this->seedItems($project, $kuihCategory, self::KUIH_ITEMS);

        $this->reorderCategories($project);

        $this->info('Done.');

        return self::SUCCESS;
    }

    private function seedItems(Project $project, Category $category, array $items): void
    {
        foreach ($items as $index => [$name, $cost, $price]) {
            $product = Product::firstOrCreate(
                ['project_id' => $project->id, 'category_id' => $category->id, 'name' => $name],
                [
                    'sku' => $category->sku_prefix.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'price' => $price,
                    'cost' => $cost,
                    'is_variable_price' => false,
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );

            if (! $product->wasRecentlyCreated) {
                $product->update(['cost' => $cost, 'price' => $price, 'is_active' => true]);
            }

            $this->info(($product->wasRecentlyCreated ? 'Created: ' : 'Updated: ').$name);
        }
    }

    /**
     * Final order: Set Nasi, Nasi (Fresh From Kelantan), Kuih (Fresh From Kelantan),
     * Ala Carte, Add-On, then anything else untouched, appended after in its existing order.
     */
    private function reorderCategories(Project $project): void
    {
        $desiredOrder = ['Set Nasi', 'Nasi (Fresh From Kelantan)', 'Kuih (Fresh From Kelantan)', 'Ala Carte', 'Add-On'];

        $categories = $project->categories()->orderBy('sort_order')->get()->keyBy('name');

        $position = 0;
        foreach ($desiredOrder as $name) {
            if ($categories->has($name)) {
                $categories[$name]->update(['sort_order' => $position]);
                $position++;
            }
        }

        foreach ($categories as $name => $category) {
            if (! in_array($name, $desiredOrder, true)) {
                $category->update(['sort_order' => $position]);
                $position++;
            }
        }

        $this->info('Category order: '.$project->categories()->orderBy('sort_order')->pluck('name')->implode(' -> '));
    }
}
