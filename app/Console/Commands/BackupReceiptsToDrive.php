<?php

namespace App\Console\Commands;

use App\Models\Purchase;
use App\Services\GoogleDriveBackupService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('app:backup-receipts-to-drive')]
#[Description('Uploads yesterday\'s Belian/Perbelanjaan receipt images to Google Drive, organised into per-date subfolders, for redundancy and tax audit purposes.')]
class BackupReceiptsToDrive extends Command
{
    public function handle(): void
    {
        $tokenPath = storage_path('app/private/google-drive-oauth-token.json');
        $rootFolderId = config('services.google_drive.backup_folder_id');

        if (! file_exists($tokenPath)) {
            $this->error("OAuth token file not found at {$tokenPath}. Visit /google-drive/authorize while logged in as owner to set it up. Skipping backup.");

            return;
        }

        if (! $rootFolderId) {
            $this->error('GOOGLE_DRIVE_BACKUP_FOLDER_ID is not set. Skipping backup.');

            return;
        }

        $token = json_decode(file_get_contents($tokenPath), true);

        if (empty($token['refresh_token']) || empty($token['client_id']) || empty($token['client_secret'])) {
            $this->error("OAuth token file at {$tokenPath} is malformed. Skipping backup.");

            return;
        }

        $since = now()->subDay()->startOfDay();
        $until = now()->startOfDay();

        $purchases = Purchase::query()
            ->whereNotNull('receipt_path')
            ->whereNull('voided_at')
            ->whereBetween('created_at', [$since, $until])
            ->get();

        if ($purchases->isEmpty()) {
            $this->info("No receipts recorded between {$since} and {$until}. Nothing to back up.");

            return;
        }

        $service = new GoogleDriveBackupService(
            $token['client_id'],
            $token['client_secret'],
            $token['refresh_token'],
            $rootFolderId,
        );

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
                $service->uploadReceipt($purchase->receipt_path, $dateFolder, $driveFileName);
                $uploaded++;
            } catch (\Throwable $e) {
                $this->error("Purchase #{$purchase->id}: upload failed - {$e->getMessage()}");
                $skipped++;
            }
        }

        $this->info("Backup done: {$uploaded} uploaded, {$skipped} skipped.");
    }
}
