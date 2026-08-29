<?php

namespace App\Console\Commands;

use App\Models\Purchase;
use App\Services\GoogleDriveBackupService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('app:test-backup-latest-receipt')]
#[Description('One-off diagnostic: upload the most recently created Purchase receipt to Google Drive, ignoring the normal "yesterday" date window, to verify the OAuth + upload flow end-to-end.')]
class TestBackupLatestReceipt extends Command
{
    public function handle(): void
    {
        $tokenPath = storage_path('app/private/google-drive-oauth-token.json');
        $rootFolderId = config('services.google_drive.backup_folder_id');

        if (! file_exists($tokenPath)) {
            $this->error("OAuth token file not found at {$tokenPath}.");

            return;
        }

        $token = json_decode(file_get_contents($tokenPath), true);

        $purchase = Purchase::query()
            ->whereNotNull('receipt_path')
            ->latest('created_at')
            ->first();

        if (! $purchase) {
            $this->info('No purchase with a receipt found at all.');

            return;
        }

        if (! Storage::disk('public')->exists($purchase->receipt_path)) {
            $this->error("Purchase #{$purchase->id}: receipt file missing on disk ({$purchase->receipt_path}).");

            return;
        }

        $service = new GoogleDriveBackupService(
            $token['client_id'],
            $token['client_secret'],
            $token['refresh_token'],
            $rootFolderId,
        );

        $dateFolder = $purchase->purchase_date->format('Y-m-d');
        $extension = pathinfo($purchase->receipt_path, PATHINFO_EXTENSION);
        $driveFileName = sprintf('TEST_%s_purchase%d.%s', $dateFolder, $purchase->id, $extension ?: 'bin');

        try {
            $fileId = $service->uploadReceipt($purchase->receipt_path, $dateFolder, $driveFileName);
            $this->info("Uploaded purchase #{$purchase->id} as {$driveFileName} into folder {$dateFolder}. Drive file id: {$fileId}");
        } catch (\Throwable $e) {
            $this->error('Upload failed: '.$e->getMessage());
        }
    }
}
