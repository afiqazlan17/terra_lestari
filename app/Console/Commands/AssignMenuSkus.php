<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:assign-menu-skus')]
#[Description('Renumbers SKU codes for Set Nasi (SN01, SN02, ...) and Ala Carte (AC01, AC02, ...) items, ordered cheapest to most expensive. Safe to re-run any time menu items or prices change.')]
class AssignMenuSkus extends Command
{
    private const CATEGORY_PREFIXES = [
        'Set Nasi' => 'SN',
        'Ala Carte' => 'AC',
    ];

    public function handle(): void
    {
        foreach (self::CATEGORY_PREFIXES as $categoryName => $prefix) {
            $category = Category::where('name', $categoryName)->first();

            if (! $category) {
                $this->warn("Category not found: {$categoryName}");

                continue;
            }

            if ($category->sku_prefix !== $prefix) {
                $category->update(['sku_prefix' => $prefix]);
            }

            $products = $category->products()->orderBy('price')->orderBy('name')->get();

            // Clear first so re-numbering never collides with a not-yet-updated
            // sibling still holding the target SKU (unique per project_id+sku).
            $products->each->update(['sku' => null]);

            foreach ($products as $index => $product) {
                $sku = $prefix.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
                $product->update(['sku' => $sku]);
                $this->info("{$product->name} -> {$sku}");
            }
        }
    }
}
