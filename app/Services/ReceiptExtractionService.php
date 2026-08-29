<?php

namespace App\Services;

use Anthropic\Client;
use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Uses Claude vision to pull the date, amount, supplier and a short
 * description off a photographed/scanned receipt, so staff can snap a
 * picture instead of typing the Belian/Perbelanjaan form by hand.
 */
class ReceiptExtractionService
{
    private const MODEL = 'claude-haiku-4-5';

    private const IMAGE_MEDIA_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
    ];

    private const PROMPT = <<<'TEXT'
        This is a photo or scan of a Malaysian business receipt or invoice (amounts in RM/MYR).
        Extract these fields as best you can, even if the image is imperfect - this only
        pre-fills a form that a human will review and correct before saving, so a best-effort
        guess is more useful than leaving a field empty.

        - purchase_date: the transaction date in YYYY-MM-DD format. If no year is printed,
          assume the current year. If truly no date is visible, use an empty string.
        - amount: the final total amount paid, as a plain number with no currency symbol or
          commas (e.g. 45.50). Use the grand total, not a subtotal. If unreadable, use 0.
        - supplier_name: the name of the shop/vendor/supplier printed on the receipt. Empty
          string if not visible.
        - description: a short 2-6 word description in Malay of what was bought (e.g.
          "Ayam dan sayur", "Bil elektrik"), inferred from the line items or receipt type.
          Empty string if you cannot tell.
        TEXT;

    public function extract(UploadedFile $file): array
    {
        $apiKey = config('services.anthropic.api_key');

        if (! $apiKey) {
            throw new RuntimeException('ANTHROPIC_API_KEY belum di-set.');
        }

        $client = new Client(apiKey: $apiKey);

        $extension = strtolower($file->getClientOriginalExtension());
        $base64 = base64_encode(file_get_contents($file->getRealPath()));

        $contentBlock = $extension === 'pdf'
            ? [
                'type' => 'document',
                'source' => ['type' => 'base64', 'mediaType' => 'application/pdf', 'data' => $base64],
            ]
            : [
                'type' => 'image',
                'source' => [
                    'type' => 'base64',
                    'mediaType' => self::IMAGE_MEDIA_TYPES[$extension] ?? 'image/jpeg',
                    'data' => $base64,
                ],
            ];

        $message = $client->messages->create(
            model: self::MODEL,
            maxTokens: 1024,
            messages: [[
                'role' => 'user',
                'content' => [$contentBlock, ['type' => 'text', 'text' => self::PROMPT]],
            ]],
            outputConfig: [
                'format' => [
                    'type' => 'json_schema',
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'purchase_date' => ['type' => 'string'],
                            'amount' => ['type' => 'number'],
                            'supplier_name' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                        ],
                        'required' => ['purchase_date', 'amount', 'supplier_name', 'description'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
        );

        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $decoded = json_decode($block->text, true);

                return is_array($decoded) ? $decoded : [];
            }
        }

        return [];
    }
}
