<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates a receipt upload by its file extension rather than Laravel's
 * built-in "mimes"/"image" rules, which call PHP's fileinfo extension to
 * guess the MIME type - not available on this shared host, causing a
 * fatal error instead of a validation failure.
 */
class ValidReceiptFile implements ValidationRule
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'pdf'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('Fail tidak sah.');

            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());

        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $fail('Fail mesti jenis JPG, PNG, atau PDF.');
        }
    }
}
