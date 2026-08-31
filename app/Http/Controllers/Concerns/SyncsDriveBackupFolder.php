<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Purchase;
use App\Services\GoogleDriveBackupService;
use Illuminate\Support\Facades\Log;

/**
 * When a receipt is corrected after it was already backed up to Google
 * Drive, the file sitting there goes stale - its parent folder is named
 * after the old date, and its filename is built from the old amount/
 * supplier/description/category. This moves and renames it to match
 * instead of leaving it stale or re-uploading a duplicate.
 */
trait SyncsDriveBackupFolder
{
    /**
     * Call after updating $purchase. Returns a warning message to flash to
     * the user if the sync failed, or null if there was nothing to do or it
     * succeeded.
     */
    private function syncDriveFileAfterEdit(Purchase $purchase, bool $dateChanged): ?string
    {
        if (! $purchase->drive_backed_up_at || ! $purchase->drive_file_id) {
            return null;
        }

        $service = GoogleDriveBackupService::fromConfig();

        if (! $service) {
            return null;
        }

        try {
            if ($dateChanged) {
                $service->moveFileToDateFolder($purchase->drive_file_id, $purchase->purchase_date->format('Y-m-d'));
            }

            $extension = pathinfo($purchase->receipt_path, PATHINFO_EXTENSION);
            $service->renameFile($purchase->drive_file_id, $purchase->driveBackupFileName($extension));

            return null;
        } catch (\Throwable $e) {
            Log::warning("Failed to sync Drive backup file for purchase #{$purchase->id} after edit: {$e->getMessage()}");

            return 'Rekod dikemaskini, tetapi fail dalam Google Drive gagal disegerakkan - sila kemaskini manual.';
        }
    }
}
