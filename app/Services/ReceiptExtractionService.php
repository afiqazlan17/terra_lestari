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
        This is a photo or scan of a Malaysian business receipt or invoice (amounts in RM/MYR),
        for a small restaurant (Sajian Baginda). Extract these fields as best you can, even if
        the image is imperfect - this only pre-fills a form that a human will review and
        correct before saving, so a best-effort guess is more useful than leaving a field empty.

        - purchase_date: the transaction/invoice/bill date in YYYY-MM-DD format - when a bill
          shows both an issue date and a payment due date, use the issue date, not the due
          date. If no year is printed, assume the current year. If truly no date is visible,
          use an empty string.
        - amount: the final total amount paid, as a plain number with no currency symbol or
          commas (e.g. 45.50). Use the grand total, not a subtotal. If unreadable, use 0.
        - supplier_name: the name of the shop/vendor/supplier printed on the receipt. Empty
          string if not visible.
        - description: a short description in Malay of what was bought, max 6 words.
          - One line item: name it directly (e.g. "Beef trim").
          - A few line items: name the 2-3 most expensive ones (e.g. "Ayam, bawang, minyak masak").
          - Many line items (e.g. a big wet-market/wholesale run): summarise the category
            instead of listing everything (e.g. "Pelbagai bahan mentah dapur", "Runcit dapur").
          Empty string if you cannot tell.
        - category: your best guess at which expense category this receipt belongs to, one of:
          - "bahan_mentah": raw ingredients/food supplies for cooking (meat, vegetables, spices,
            cooking oil, rice, wet-market or grocery wholesale runs)
          - "sewa": rent
          - "utiliti": utility bills (electricity/TNB, water, internet, phone)
          - "gaji": staff salary/wages
          - "renovasi": renovation, repairs, fixtures, equipment
          - "lain_lain": anything else (packaging, cleaning supplies, marketing/printing, etc.)
          Default to "bahan_mentah" if genuinely unclear and it looks food-related, otherwise
          "lain_lain".
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
                            'category' => [
                                'type' => 'string',
                                'enum' => ['bahan_mentah', 'sewa', 'utiliti', 'gaji', 'renovasi', 'lain_lain'],
                            ],
                        ],
                        'required' => ['purchase_date', 'amount', 'supplier_name', 'description', 'category'],
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
