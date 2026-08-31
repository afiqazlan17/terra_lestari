<?php

// Emergency vendor/ replacement that does NOT boot Laravel at all - pure
// PHP file operations, so it works even when the framework itself can't
// boot. Requires ?key=... to match. Delete this file once resolved.
$expectedKey = 'sb-emergency-2026-08-31';

if (($_GET['key'] ?? '') !== $expectedKey) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

header('Content-Type: text/plain');

$root = dirname(__DIR__);
$prefix = $_GET['prefix'] ?? 'vendorfix2part';

$parts = glob($root.'/'.$prefix.'*');
sort($parts);

if (empty($parts)) {
    echo "No parts found matching {$prefix}* in {$root}\n";
    exit;
}

echo 'Found '.count($parts)." parts: ".implode(', ', array_map('basename', $parts))."\n";

$zipPath = $root.'/vendor-reassembled-emergency.zip';
$out = fopen($zipPath, 'wb');

foreach ($parts as $part) {
    $in = fopen($part, 'rb');
    stream_copy_to_stream($in, $out);
    fclose($in);
}

fclose($out);
echo 'Reassembled into '.filesize($zipPath)." bytes.\n";

$zip = new ZipArchive();
$result = $zip->open($zipPath);

if ($result !== true) {
    echo "Failed to open reassembled zip (ZipArchive error code {$result}).\n";
    exit;
}

echo "Zip opened OK, {$zip->numFiles} entries.\n";

function deleteDirectory(string $dir): void
{
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $dir.DIRECTORY_SEPARATOR.$item;

        if (is_link($path)) {
            unlink($path);
        } elseif (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }

    rmdir($dir);
}

$vendorDir = $root.'/vendor';

if (is_dir($vendorDir)) {
    deleteDirectory($vendorDir);
    echo "Removed existing vendor/.\n";
}

if (! $zip->extractTo($root)) {
    echo "Extraction failed.\n";
    $zip->close();
    exit;
}

$zip->close();
echo "Extracted fresh vendor/.\n";

unlink($zipPath);

foreach ($parts as $part) {
    unlink($part);
}

echo "Deleted the reassembled zip and part files.\n";

// Also clear bootstrap cache - plain file deletion, no artisan needed.
foreach (['packages.php', 'services.php'] as $cacheFile) {
    $path = $root.'/bootstrap/cache/'.$cacheFile;
    if (file_exists($path)) {
        unlink($path);
        echo "Deleted bootstrap/cache/{$cacheFile}.\n";
    }
}

if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache reset.\n";
}

echo "DONE.\n";
