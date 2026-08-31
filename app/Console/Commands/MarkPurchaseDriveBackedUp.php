<?php

namespace App\Console\Commands;

use App\Models\Purchase;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Command;

#[Signature('app:mark-purchase-drive-backed-up {date : purchase_date, e.g. 2026-08-29} {amount : exact amount, e.g. 72.20}')]
#[Description('One-off: marks a specific Purchase as already backed up to Google Drive (sets drive_backed_up_at), for a receipt that was uploaded under the old backup job before drive_backed_up_at existed - so the new idempotent backup job does not re-upload it as a duplicate. Only acts when exactly one match is found; otherwise lists what it found and does nothing.')]
class MarkPurchaseDriveBackedUp extends Command
{
    public function handle(): int
    {
        $matches = Purchase::whereDate('purchase_date', $this->argument('date'))
            ->where('amount', $this->argument('amount'))
            ->whereNotNull('receipt_path')
            ->whereNull('drive_backed_up_at')
            ->get();

        if ($matches->isEmpty()) {
            $this->info('No matching un-backed-up purchase found. Nothing to do.');

            return self::SUCCESS;
        }

        if ($matches->count() > 1) {
            $this->error("Found {$matches->count()} matches - refusing to guess which one. Details:");
            foreach ($matches as $m) {
                $this->line("  id={$m->id} date={$m->purchase_date->toDateString()} amount={$m->amount} supplier={$m->supplier_name} desc={$m->description}");
            }

            return self::FAILURE;
        }

        $purchase = $matches->first();
        $this->info("Matched: id={$purchase->id} date={$purchase->purchase_date->toDateString()} amount={$purchase->amount} supplier={$purchase->supplier_name} desc={$purchase->description}");

        $purchase->update(['drive_backed_up_at' => now()]);
        $this->info('Marked as backed up.');

        return self::SUCCESS;
    }
}
