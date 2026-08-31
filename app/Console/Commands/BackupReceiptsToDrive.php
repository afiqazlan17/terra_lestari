<?php

namespace App\Console\Commands;

use App\Models\Purchase;
use App\Services\GoogleDriveBackupService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('app:backup-receipts-to-drive')]
#[Description('Uploads any not-yet-backed-up Belian/Perbelanjaan receipt images to Google Drive, organised into per-date subfolders (by the receipt\'s own date, not when it was entered), for redundancy and tax audit purposes. Idempotent regardless of what time it runs - only ever processes receipts that have never been uploaded before.')]
class BackupReceiptsToDrive extends Command
{
    public function handle(): void
    {
        $service = GoogleDriveBackupService::fromConfig();

        if (! $service) {
            $this->error('Google Drive OAuth token or GOOGLE_DRIVE_BACKUP_FOLDER_ID is not set up. Visit /google-drive/authorize while logged in as owner. Skipping backup.');

            return;
        }

        $purchases = Purchase::query()
            ->whereNotNull('receipt_path')
            ->whereNull('voided_at')
            ->whereNull('drive_backed_up_at')
            ->get();

        if ($purchases->isEmpty()) {
            $this->info('No unbacked-up receipts found. Nothing to back up.');

            return;
        }

        $uploaded = 0;
        $skipped = 0;

        foreach ($purchases as $purchase) {
            if (! Storage::disk('public')->exists($purchase->receipt_path)) {
                $this->warn("Purchase #{$purchase->id}: receipt file missing on disk ({$purchase->receipt_path}), skipping.");
                $skipped++;

                continue;
            }

            $dateFolder = $purchase->purchase_date->format('Y-m-d');
            $extension = pathinfo($purchase->receipt_path, PATHINFO_EXTENSION);
            $driveFileName = $purchase->driveBackupFileName($extension);

            try {
                $fileId = $service->uploadReceipt($purchase->receipt_path, $dateFolder, $driveFileName);
                $purchase->update(['drive_backed_up_at' => now(), 'drive_file_id' => $fileId]);
                $uploaded++;
            } catch (\Throwable $e) {
                $this->error("Purchase #{$purchase->id}: upload failed - {$e->getMessage()}");
                $skipped++;
            }
        }

        $this->info("Backup done: {$uploaded} uploaded, {$skipped} skipped.");
    }
}
