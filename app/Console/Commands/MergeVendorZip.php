<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use ZipArchive;

#[Signature('app:merge-vendor-zip {zip : Path to the vendor delta zip, relative to the project root}')]
#[Description('One-off: extracts a partial vendor/ zip (e.g. just the new packages a composer.json change added) on top of the existing vendor/ directory, WITHOUT deleting it first - unlike app:deploy-build-assets, this merges rather than replacing wholesale, since the zip only contains new/changed packages, not the whole vendor tree. Deletes the zip after a successful extract.')]
class MergeVendorZip extends Command
{
    public function handle(): int
    {
        $zipPath = base_path($this->argument('zip'));

        if (! is_file($zipPath)) {
            $this->error("Zip not found: {$zipPath}");

            return self::FAILURE;
        }

        $zip = new ZipArchive;
        $result = $zip->open($zipPath);

        if ($result !== true) {
            $this->error("Failed to open zip (ZipArchive error code {$result}).");

            return self::FAILURE;
        }

        $this->info("Zip opened OK, {$zip->numFiles} entries.");

        if (! $zip->extractTo(base_path('vendor'))) {
            $this->error('Extraction failed.');
            $zip->close();

            return self::FAILURE;
        }

        $zip->close();
        $this->info('Merged into vendor/.');

        unlink($zipPath);
        $this->info('Deleted the uploaded zip.');

        return self::SUCCESS;
    }
}
