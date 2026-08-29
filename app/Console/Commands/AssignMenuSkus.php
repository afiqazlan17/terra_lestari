<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:assign-menu-skus')]
#[Description('One-off: assign SKU codes to the Set Nasi (SN01-07) and Ala Carte (AC01-07) menu items, ordered cheapest to most expensive.')]
class AssignMenuSkus extends Command
{
    private const SKUS = [
        'Nasi Berlauk Ayam Budget (Bungkus)' => 'SN01',
        'Nasi Gulai Ayam' => 'SN02',
        'Nasi Keli Goreng' => 'SN03',
        'Nasi Ayam Cincang' => 'SN04',
        'Nasi Gulai Ikan Tongkol' => 'SN05',
        'Nasi Daging Berlengas' => 'SN06',
        'Nasi Sotong Goreng Tepung' => 'SN07',
        'Nasi Putih' => 'AC01',
        'Ayam Gulai' => 'AC02',
        'Keli Goreng' => 'AC03',
        'Ayam Cincang' => 'AC04',
        'Ikan Tongkol' => 'AC05',
        'Daging Berlengas' => 'AC06',
        'Sotong Goreng Tepung' => 'AC07',
    ];

    public function handle(): void
    {
        foreach (self::SKUS as $name => $sku) {
            $product = Product::where('name', $name)->first();

            if (! $product) {
                $this->warn("Skipped (not found): {$name}");

                continue;
            }

            $product->update(['sku' => $sku]);
            $this->info("{$product->name} -> {$sku}");
        }
    }
}
