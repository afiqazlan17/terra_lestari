<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use ZipArchive;

#[Signature('app:deploy-build-assets {zip=build-assets.zip : Path to the built public/build zip, relative to the project root}')]
#[Description('One-off: replaces public/build with the contents of a zip built locally via `npm run build` and uploaded to the project root. Uses PHP\'s ZipArchive instead of the shell `unzip` binary, which is unreliable/unavailable in this host\'s cron environment. Deletes the zip after a successful extract.')]
class DeployBuildAssets extends Command
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

        $buildDir = public_path('build');

        if (is_dir($buildDir)) {
            $this->deleteDirectory($buildDir);
            $this->info('Removed existing public/build.');
        }

        if (! $zip->extractTo(public_path())) {
            $this->error('Extraction failed.');
            $zip->close();

            return self::FAILURE;
        }

        $zip->close();
        $this->info('Extracted to public/.');

        unlink($zipPath);
        $this->info('Deleted the uploaded zip.');

        $manifestPath = public_path('build/manifest.json');

        if (is_file($manifestPath)) {
            $this->info('manifest.json contents: '.file_get_contents($manifestPath));
        } else {
            $this->warn('public/build/manifest.json not found after extraction - the zip may not have contained the expected structure.');
        }

        return self::SUCCESS;
    }

    private function deleteDirectory(string $dir): void
    {
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir.DIRECTORY_SEPARATOR.$item;

            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
