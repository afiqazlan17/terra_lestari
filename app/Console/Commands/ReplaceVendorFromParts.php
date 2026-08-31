<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use ZipArchive;

#[Signature('app:replace-vendor-from-parts {prefix : Filename prefix of the split zip parts in the project root, e.g. vendor-full-part}')]
#[Description('Emergency recovery: concatenates split vendor.zip parts (uploaded to the project root, named <prefix>00, <prefix>01, ...) back into one zip, deletes vendor/ entirely, and extracts the reassembled zip fresh - a full replace, not a merge, to guarantee an internally consistent vendor tree. Deletes the parts and the temp zip after a successful extract.')]
class ReplaceVendorFromParts extends Command
{
    public function handle(): int
    {
        $prefix = $this->argument('prefix');
        $parts = collect(glob(base_path($prefix).'*'))
            ->filter(fn ($path) => is_file($path))
            ->sort()
            ->values();

        if ($parts->isEmpty()) {
            $this->error("No parts found matching {$prefix}*");

            return self::FAILURE;
        }

        $this->info('Found '.$parts->count().' parts: '.$parts->map(fn ($p) => basename($p))->implode(', '));

        $zipPath = base_path('vendor-reassembled.zip');
        $out = fopen($zipPath, 'wb');

        foreach ($parts as $part) {
            $in = fopen($part, 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
        }

        fclose($out);
        $this->info('Reassembled into '.filesize($zipPath).' bytes.');

        $zip = new ZipArchive;
        $result = $zip->open($zipPath);

        if ($result !== true) {
            $this->error("Failed to open reassembled zip (ZipArchive error code {$result}).");

            return self::FAILURE;
        }

        $this->info("Zip opened OK, {$zip->numFiles} entries.");

        $vendorDir = base_path('vendor');

        if (is_dir($vendorDir)) {
            $this->deleteDirectory($vendorDir);
            $this->info('Removed existing vendor/.');
        }

        if (! $zip->extractTo(base_path())) {
            $this->error('Extraction failed.');
            $zip->close();

            return self::FAILURE;
        }

        $zip->close();
        $this->info('Extracted fresh vendor/.');

        unlink($zipPath);

        foreach ($parts as $part) {
            unlink($part);
        }

        $this->info('Deleted the reassembled zip and part files.');

        return self::SUCCESS;
    }

    private function deleteDirectory(string $dir): void
    {
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir.DIRECTORY_SEPARATOR.$item;

            if (is_link($path)) {
                unlink($path);
            } elseif (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
