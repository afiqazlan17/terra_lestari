<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Purchase;
use App\Services\GoogleDriveBackupService;
use Illuminate\Support\Facades\Log;

/**
 * When a receipt's purchase_date is corrected after it was already backed
 * up to Google Drive, the file is sitting in the (now wrong) date subfolder.
 * This moves it into the correct one instead of leaving it stale or
 * re-uploading a duplicate.
 */
trait SyncsDriveBackupFolder
{
    /**
     * Call after updating $purchase's purchase_date. Returns a warning
     * message to flash to the user if the move failed, or null if there was
     * nothing to do or it succeeded.
     */
    private function syncDriveFolderForDateChange(Purchase $purchase): ?string
    {
        if (! $purchase->drive_backed_up_at || ! $purchase->drive_file_id) {
            return null;
        }

        $service = GoogleDriveBackupService::fromConfig();

        if (! $service) {
            return null;
        }

        try {
            $service->moveFileToDateFolder($purchase->drive_file_id, $purchase->purchase_date->format('Y-m-d'));

            return null;
        } catch (\Throwable $e) {
            Log::warning("Failed to move Drive backup file for purchase #{$purchase->id} after date change: {$e->getMessage()}");

            return 'Tarikh dikemaskini, tetapi fail dalam Google Drive gagal dipindah ke folder baru - sila pindah manual.';
        }
    }
}
