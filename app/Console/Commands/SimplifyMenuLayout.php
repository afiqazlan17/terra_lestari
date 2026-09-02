<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\Project;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:simplify-menu-layout')]
#[Description('One-off: switches Set Nasi / Ala Carte / Add-On to square tiles with a fixed column count, shortens Set Nasi names, collapses the 11-item "Nasi (Fresh From Kelantan)" list to 4 same-priced dishes (deactivating the rest), and replaces the 23 named Kuih items with 7 generic price-tier "Kuih Muih" products (their cost set to the average cost of the real dishes at that price). Safe to re-run.')]
class SimplifyMenuLayout extends Command
{
    /** old name => new (shortened) name */
    private const SET_NASI_RENAMES = [
        'Nasi Sotong Goreng Tepung' => 'Sotong Goreng Tepung',
        'Nasi Gulai Ikan Tongkol' => 'Gulai Ikan Tongkol',
        'Nasi Daging Berlengas' => 'Daging Berlengas',
        'Nasi Ayam Cincang' => 'Ayam Cincang',
        'Nasi Gulai Ayam' => 'Gulai Ayam',
        'Nasi Keli Goreng' => 'Keli Goreng',
        'Nasi Berlauk Ayam Budget (Bungkus)' => 'Berlauk Ayam',
    ];

    /** old name => [new name, color_tier] - the 4 NBK Nasi dishes kept active. */
    private const NASI_NBK_KEEP = [
        'Nasi Kerabu Daging Goreng (Air Dingin)' => ['Kerabu Daging', 1],
        'Nasi Kerabu Ayam Goreng (Air Dingin)' => ['Kerabu Ayam', 1],
        'Nasi Dagang Ikan (Air Dingin)' => ['Dagang Ikan', 2],
        'Nasi Dagang Ayam (Air Dingin)' => ['Dagang Ayam', 2],
    ];

    public function handle(): int
    {
        $project = Project::where('is_active', true)->orderBy('id')->first();

        if (! $project) {
            $this->error('No active project found.');

            return self::FAILURE;
        }

        $this->applyCategoryLayout($project);
        $this->renameSetNasi($project);
        $this->collapseNasiNbk($project);
        $this->collapseKuihNbk($project);

        $this->info('Selesai.');

        return self::SUCCESS;
    }

    private function applyCategoryLayout(Project $project): void
    {
        $layouts = [
            'Set Nasi' => 4,
            'Nasi (Fresh From Kelantan)' => 4,
            'Kuih (Fresh From Kelantan)' => 4,
            'Ala Carte' => 5,
            'Add-On' => 4,
        ];

        foreach ($layouts as $name => $columns) {
            $updated = Category::where('project_id', $project->id)
                ->where('name', $name)
                ->update(['tile_shape' => 'square', 'grid_columns' => $columns]);

            $this->info($updated ? "Kategori \"{$name}\": square, {$columns} lajur." : "Kategori \"{$name}\" tidak dijumpai - dilangkau.");
        }
    }

    private function renameSetNasi(Project $project): void
    {
        $category = Category::where('project_id', $project->id)->where('name', 'Set Nasi')->first();

        if (! $category) {
            return;
        }

        foreach (self::SET_NASI_RENAMES as $old => $new) {
            $renamed = Product::where('category_id', $category->id)->where('name', $old)->update(['name' => $new]);

            if ($renamed) {
                $this->info("Set Nasi: \"{$old}\" -> \"{$new}\".");
            }
        }
    }

    private function collapseNasiNbk(Project $project): void
    {
        $category = Category::where('project_id', $project->id)->where('name', 'Nasi (Fresh From Kelantan)')->first();

        if (! $category) {
            $this->warn('Kategori "Nasi (Fresh From Kelantan)" tidak dijumpai - dilangkau.');

            return;
        }

        foreach (self::NASI_NBK_KEEP as $old => [$new, $tier]) {
            $updated = Product::where('category_id', $category->id)->where('name', $old)
                ->update(['name' => $new, 'is_active' => true, 'color_tier' => $tier]);

            if ($updated) {
                $this->info("Nasi NBK: \"{$old}\" -> \"{$new}\" (aktif, warna tier {$tier}).");
            }
        }

        $keptNames = array_map(fn ($v) => $v[0], self::NASI_NBK_KEEP);

        $deactivated = Product::where('category_id', $category->id)
            ->whereNotIn('name', $keptNames)
            ->where('is_active', true)
            ->update(['is_active' => false, 'color_tier' => null]);

        $this->info("Nasi NBK: {$deactivated} produk lain dinyahaktifkan.");
    }

    private function collapseKuihNbk(Project $project): void
    {
        $category = Category::where('project_id', $project->id)->where('name', 'Kuih (Fresh From Kelantan)')->first();

        if (! $category) {
            $this->warn('Kategori "Kuih (Fresh From Kelantan)" tidak dijumpai - dilangkau.');

            return;
        }

        $realKuih = Product::where('category_id', $category->id)
            ->where('name', '!=', 'Kuih Muih')
            ->where('is_active', true)
            ->whereNotNull('cost')
            ->get();

        $priceGroups = $realKuih->groupBy(fn ($p) => number_format((float) $p->price, 2));

        foreach ($priceGroups as $priceStr => $products) {
            $avgCost = round($products->avg(fn ($p) => (float) $p->cost), 2);

            $product = Product::firstOrCreate(
                ['project_id' => $project->id, 'category_id' => $category->id, 'name' => 'Kuih Muih', 'price' => $priceStr],
                ['sku' => $category->nextSku(), 'cost' => $avgCost, 'is_variable_price' => false, 'is_active' => true, 'sort_order' => 0]
            );

            if (! $product->wasRecentlyCreated) {
                $product->update(['cost' => $avgCost, 'is_active' => true]);
            }

            $this->info("Kuih Muih RM{$priceStr}: kos purata RM{$avgCost} ({$products->count()} item asal).");
        }

        $deactivated = Product::where('category_id', $category->id)
            ->where('name', '!=', 'Kuih Muih')
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $this->info("Kuih NBK: {$deactivated} produk asal dinyahaktifkan.");
    }
}
