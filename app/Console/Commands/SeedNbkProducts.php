<?php

namespace App\Console\Commands;

use App\Models\NbkProduct;
use App\Models\Project;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:seed-nbk-products')]
#[Description('One-off: seeds the NBK (Nasi Berlauk Kelantan) vendor product catalog with name, unit cost and minimum order quantity, from the vendor\'s order form. Safe to re-run - skips items that already exist by name.')]
class SeedNbkProducts extends Command
{
    /** [name, unit_cost, min_qty] */
    private const PRODUCTS = [
        ['NASI BERLAUK AYAM GULAI (FIZOW KITCHEN)', 16.50, 3],
        ['NASI BERLAUK AYAM KERUTUK (FIZOW KITCHEN)', 16.50, 3],
        ['NASI BERLAUK AYAM PEDAS (FIZOW KITCHEN)', 16.50, 3],
        ['NASI BERLAUK DAGING BERLENGAS (FIZOW KITCHEN)', 18.00, 3],
        ['IKAN GULAI (KACHIK)', 4.20, 1],
        ['AYAM GULAI (KACHIK)', 4.50, 1],
        ['AYAM GORENG (KACHIK)', 4.50, 1],
        ['AYAM PEDAS (KACHIK)', 4.50, 1],
        ['DAGING GORENG (KACHIK)', 4.70, 1],
        ['DAGING KERUTUK (KACHIK)', 4.70, 1],
        ['NASI DAGANG IKAN (KACHIK)', 4.50, 1],
        ['NASI DAGANG AYAM (KACHIK)', 4.60, 1],
        ['IKAN GULAI (AIR DINGIN)', 12.90, 3],
        ['IKAN PEDAS (AIR DINGIN)', 12.00, 3],
        ['AYAM GULAI (AIR DINGIN)', 13.20, 3],
        ['AYAM PEDAS (AIR DINGIN)', 13.20, 3],
        ['AYAM GORENG (AIR DINGIN)', 13.20, 3],
        ['DAGING GORENG (AIR DINGIN)', 13.80, 3],
        ['DAGING KERUTUK (AIR DINGIN)', 13.80, 3],
        ['NASI DAGANG IKAN (AIR DINGIN)', 13.20, 3],
        ['NASI DAGANG AYAM (AIR DINGIN)', 13.50, 3],
        ['NASI DAGANG DAGING KERUTUK (AIR DINGIN)', 13.20, 3],
        ['NASI KERABU AYAM GORENG (AIR DINGIN)', 14.10, 3],
        ['NASI KERABU DAGING GORENG (AIR DINGIN)', 15.00, 3],
        ['IKAN GULAI (TNB)', 8.80, 2],
        ['AYAM GULAI (TNB)', 8.80, 2],
        ['AYAM PEDAS (TNB)', 4.40, 1],
        ['AYAM GORENG (TNB)', 8.80, 2],
        ['DAGING GORENG (TNB)', 9.20, 2],
        ['DAGING KERUTUK (TNB)', 9.20, 2],
        ['AYAM GULAI KAMPUNG (TNB)', 9.20, 2],
        ['NASI GULAI IKAN KERING', 5.00, 1],
        ['NASI KERABU PALOH (TELUR MASIN)', 4.00, 1],
        ['NASI KERABU PALOH (AYAM BAKAR)', 5.00, 1],
        ['NASI KERABU PALOH (DAGING BAKAR)', 5.00, 1],
        ['NASI KERABU PALOH (IKAN CELUP TEPUNG)', 5.00, 1],
        ['NASI KERABU PALOH (DAGING BAKAR + TELUR MASIN)', 6.00, 1],
        ['NASI KERABU PALOH (AYAM BAKAR + TELUR MASIN)', 6.00, 1],
        ['NASI KERABU PALOH (IKAN CELUP TEPUNG + TELUR MASIN)', 6.00, 1],
        ['NASI LEMAK TELUR SEBIJI', 8.10, 3],
        ['NASI TUMPANG', 13.80, 3],
        ['CEKMEK (4PCS)', 6.60, 3],
        ['BEKO NYOR', 7.50, 3],
        ['KUIH TOPI (3 PCS)', 4.50, 3],
        ['LEPAT PISANG', 5.40, 3],
        ['TEPUNG GOMOK (5 PCS)', 5.70, 3],
        ['PULUT PAGI BIASA', 5.10, 3],
        ['PULUT PAGI KACANG', 5.40, 3],
        ['PULUT IKAN REBUS', 7.50, 3],
        ['PULUT IKAN KERING', 6.90, 3],
        ['NASI IMPIT SATEY', 8.40, 3],
        ['NASI IMPIT SAMBAL', 5.40, 3],
        ['NASI IMPIT KUAH KACANG', 6.60, 3],
        ['KUIH PERIA (4PCS)', 6.60, 3],
        ['LOMPAT TIKAM', 9.00, 3],
        ['AKOK BIASA 5 BIJI (BERANGAN)', 6.00, 3],
        ['AKOK PANDAN 5 BIJI (BERANGAN)', 6.00, 3],
        ['AKOK PIANA 1 BEKAS (BERANGAN)', 12.00, 3],
        ['KUIH BAKAR 1 PEK (BERANGAN)', 1.80, 1],
        ['PULUT INTI (BERANGAN)', 5.40, 3],
        ['PULUT PAGI BIASA (BERANGAN)', 5.40, 3],
        ['PULUT PAGI KACANG (BERANGAN)', 5.40, 3],
        ['PULUT IKAN REBUS (BERANGAN)', 7.50, 3],
        ['APAM PURI', 8.40, 3],
        ['APAM BAKAR', 8.40, 3],
        ['BUAH TANJONG', 9.00, 3],
        ['JALA MAS (4 BIJI)', 9.00, 3],
        ['TAHI ITIK (5 BIJI)', 9.00, 3],
        ['3 SERANGKAI', 9.00, 3],
    ];

    /** Out of stock / not yet started on the vendor's form - no price listed. Seeded inactive with placeholder values; edit once stock is available. */
    private const OUT_OF_STOCK_PRODUCTS = [
        'NASI DAGANG DAGING KERUTUK (KACHIK)',
        'TEPUNG BUNGKUS KELATE (4pc)',
        'LEPAT UBI KAYU (3pcs)',
        'PELEBAT UBI',
        'PULUT LEPO SERUNDING IKAN',
        'PULUT LEPO SERUNDING DAGING',
        'PULUT BAKAR KOSONG',
        'PULUT BAKAR SERUNDING IKAN',
        'PULUT BAKAR SERUNDING DAGING',
    ];

    public function handle(): void
    {
        $project = Project::where('is_active', true)->orderBy('id')->first();

        if (! $project) {
            $this->error('No active project found.');

            return;
        }

        $created = 0;
        $skipped = 0;

        foreach (self::PRODUCTS as $index => [$name, $unitCost, $minQty]) {
            $product = NbkProduct::firstOrCreate(
                ['project_id' => $project->id, 'name' => $name],
                ['unit_cost' => $unitCost, 'min_qty' => $minQty, 'is_active' => true, 'sort_order' => $index]
            );

            if ($product->wasRecentlyCreated) {
                $created++;
            } else {
                $skipped++;
            }
        }

        $offset = count(self::PRODUCTS);

        foreach (self::OUT_OF_STOCK_PRODUCTS as $index => $name) {
            $product = NbkProduct::firstOrCreate(
                ['project_id' => $project->id, 'name' => $name],
                ['unit_cost' => 0, 'min_qty' => 1, 'is_active' => false, 'sort_order' => $offset + $index]
            );

            if ($product->wasRecentlyCreated) {
                $created++;
            } else {
                $skipped++;
            }
        }

        $this->info("Done: {$created} created, {$skipped} already existed.");
    }
}
