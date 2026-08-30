<?php

namespace App\Console\Commands;

use App\Models\NbkProduct;
use App\Models\Project;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:seed-nbk-products')]
#[Description('One-off: seeds the NBK (Nasi Berlauk Kelantan) vendor product catalog with per-unit cost and minimum order quantity, in the same order as the vendor\'s order form. Unit cost is the vendor\'s bundle price divided by the minimum quantity. Items listed as HABIS STOK/BELUM MULA on the form are seeded inactive with a placeholder cost - edit once stock is available. Safe to re-run - skips items that already exist by name.')]
class SeedNbkProducts extends Command
{
    /** [name, unit_cost, min_qty] in the vendor form's order. unit_cost is per single unit (vendor's listed price / min_qty). Null cost/qty = out of stock on the form. */
    private const PRODUCTS = [
        ['NASI BERLAUK AYAM GULAI (FIZOW KITCHEN)', 5.50, 3],
        ['NASI BERLAUK AYAM KERUTUK (FIZOW KITCHEN)', 5.50, 3],
        ['NASI BERLAUK AYAM PEDAS (FIZOW KITCHEN)', 5.50, 3],
        ['NASI BERLAUK DAGING BERLENGAS (FIZOW KITCHEN)', 6.00, 3],
        ['IKAN GULAI (KACHIK)', 4.20, 1],
        ['AYAM GULAI (KACHIK)', 4.50, 1],
        ['AYAM GORENG (KACHIK)', 4.50, 1],
        ['AYAM PEDAS (KACHIK)', 4.50, 1],
        ['DAGING GORENG (KACHIK)', 4.70, 1],
        ['DAGING KERUTUK (KACHIK)', 4.70, 1],
        ['NASI DAGANG IKAN (KACHIK)', 4.50, 1],
        ['NASI DAGANG AYAM (KACHIK)', 4.60, 1],
        ['NASI DAGANG DAGING KERUTUK (KACHIK)', null, null],
        ['IKAN GULAI (AIR DINGIN)', 4.30, 3],
        ['IKAN PEDAS (AIR DINGIN)', 4.00, 3],
        ['AYAM GULAI (AIR DINGIN)', 4.40, 3],
        ['AYAM PEDAS (AIR DINGIN)', 4.40, 3],
        ['AYAM GORENG (AIR DINGIN)', 4.40, 3],
        ['DAGING GORENG (AIR DINGIN)', 4.60, 3],
        ['DAGING KERUTUK (AIR DINGIN)', 4.60, 3],
        ['NASI DAGANG IKAN (AIR DINGIN)', 4.40, 3],
        ['NASI DAGANG AYAM (AIR DINGIN)', 4.50, 3],
        ['NASI DAGANG DAGING KERUTUK (AIR DINGIN)', 4.40, 3],
        ['NASI KERABU AYAM GORENG (AIR DINGIN)', 4.70, 3],
        ['NASI KERABU DAGING GORENG (AIR DINGIN)', 5.00, 3],
        ['IKAN GULAI (TNB)', 4.40, 2],
        ['AYAM GULAI (TNB)', 4.40, 2],
        ['AYAM PEDAS (TNB)', 4.40, 1],
        ['AYAM GORENG (TNB)', 4.40, 2],
        ['DAGING GORENG (TNB)', 4.60, 2],
        ['DAGING KERUTUK (TNB)', 4.60, 2],
        ['AYAM GULAI KAMPUNG (TNB)', 4.60, 2],
        ['NASI GULAI IKAN KERING', 5.00, 1],
        ['NASI KERABU PALOH (TELUR MASIN)', 4.00, 1],
        ['NASI KERABU PALOH (AYAM BAKAR)', 5.00, 1],
        ['NASI KERABU PALOH (DAGING BAKAR)', 5.00, 1],
        ['NASI KERABU PALOH (IKAN CELUP TEPUNG)', 5.00, 1],
        ['NASI KERABU PALOH (DAGING BAKAR + TELUR MASIN)', 6.00, 1],
        ['NASI KERABU PALOH (AYAM BAKAR + TELUR MASIN)', 6.00, 1],
        ['NASI KERABU PALOH (IKAN CELUP TEPUNG + TELUR MASIN)', 6.00, 1],
        ['NASI LEMAK TELUR SEBIJI', 2.70, 3],
        ['NASI TUMPANG', 4.60, 3],
        ['TEPUNG BUNGKUS KELATE (4pc)', null, null],
        ['CEKMEK (4PCS)', 2.20, 3],
        ['BEKO NYOR', 2.50, 3],
        ['LEPAT UBI KAYU (3pcs)', null, null],
        ['KUIH TOPI (3 PCS)', 1.50, 3],
        ['PELEBAT UBI', null, null],
        ['LEPAT PISANG', 1.80, 3],
        ['TEPUNG GOMOK (5 PCS)', 1.90, 3],
        ['PULUT PAGI BIASA', 1.70, 3],
        ['PULUT PAGI KACANG', 1.80, 3],
        ['PULUT IKAN REBUS', 2.50, 3],
        ['PULUT IKAN KERING', 2.30, 3],
        ['NASI IMPIT SATEY', 2.80, 3],
        ['NASI IMPIT SAMBAL', 1.80, 3],
        ['NASI IMPIT KUAH KACANG', 2.20, 3],
        ['PULUT LEPO SERUNDING IKAN', null, null],
        ['PULUT LEPO SERUNDING DAGING', null, null],
        ['PULUT BAKAR KOSONG', null, null],
        ['PULUT BAKAR SERUNDING IKAN', null, null],
        ['PULUT BAKAR SERUNDING DAGING', null, null],
        ['KUIH PERIA (4PCS)', 2.20, 3],
        ['LOMPAT TIKAM', 3.00, 3],
        ['AKOK BIASA 5 BIJI (BERANGAN)', 2.00, 3],
        ['AKOK PANDAN 5 BIJI (BERANGAN)', 2.00, 3],
        ['AKOK PIANA 1 BEKAS (BERANGAN)', 4.00, 3],
        ['KUIH BAKAR 1 PEK (BERANGAN)', 1.80, 1],
        ['PULUT INTI (BERANGAN)', 1.80, 3],
        ['PULUT PAGI BIASA (BERANGAN)', 1.80, 3],
        ['PULUT PAGI KACANG (BERANGAN)', 1.80, 3],
        ['PULUT IKAN REBUS (BERANGAN)', 2.50, 3],
        ['APAM PURI', 2.80, 3],
        ['APAM BAKAR', 2.80, 3],
        ['BUAH TANJONG', 3.00, 3],
        ['JALA MAS (4 BIJI)', 3.00, 3],
        ['TAHI ITIK (5 BIJI)', 3.00, 3],
        ['3 SERANGKAI', 3.00, 3],
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
            $inStock = $unitCost !== null;

            $product = NbkProduct::firstOrCreate(
                ['project_id' => $project->id, 'name' => $name],
                [
                    'unit_cost' => $inStock ? $unitCost : 0,
                    'min_qty' => $inStock ? $minQty : 1,
                    'is_active' => $inStock,
                    'sort_order' => $index,
                ]
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
