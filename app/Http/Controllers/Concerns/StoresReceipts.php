<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Stores an uploaded receipt under an explicit filename derived from the
 * client-provided extension, rather than letting Laravel's store() generate
 * one via hashName() - that calls guessExtension() internally, which needs
 * PHP's fileinfo extension (not available on this host).
 */
trait StoresReceipts
{
    private function storeReceipt(UploadedFile $file, string $directory): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = Str::random(40).'.'.$extension;

        return $file->storeAs($directory, $filename, 'public');
    }
}
