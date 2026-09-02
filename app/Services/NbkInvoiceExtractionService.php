<?php

namespace App\Services;

use Anthropic\Client;
use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Uses Claude vision to pull line items (product name + quantity only - no
 * prices, since order totals are always calculated from our own NBK
 * catalog prices, not whatever the invoice prints) off a photographed or
 * scanned NBK vendor invoice, so staff can snap/upload the invoice instead
 * of typing every quantity into the order calculator by hand.
 */
class NbkInvoiceExtractionService
{
    private const MODEL = 'claude-haiku-4-5';

    private const IMAGE_MEDIA_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
    ];

    private const PROMPT = <<<'TEXT'
        This is a photo, scan, or PDF of a vendor invoice/delivery memo from "NBK - Nasi
        Berlauk Kelantan", a food supplier, addressed to a small restaurant (Sajian
        Baginda). Extract every line item as best you can, even if the image is
        imperfect - this only pre-fills quantities in an order form that a human will
        review and correct before saving, so a best-effort guess is more useful than
        leaving items out.

        For each line item, extract:
        - name: the product/dish name as printed (e.g. "Gulai Ayam", "Dagang Ikan").
          Do not translate or rename it - use exactly what's on the invoice.
        - qty: the quantity/unit count for that line, as a whole number. If a unit is
          printed (pkt, bungkus, kg, etc.) just use the numeric count, ignore the unit.

        Ignore prices, subtotals, totals, and any non-item lines (headers, addresses,
        signatures, terms). If the image has no readable line items at all, return an
        empty items array.
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
                            'items' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => ['type' => 'string'],
                                        'qty' => ['type' => 'integer'],
                                    ],
                                    'required' => ['name', 'qty'],
                                    'additionalProperties' => false,
                                ],
                            ],
                        ],
                        'required' => ['items'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
        );

        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $decoded = json_decode($block->text, true);

                return is_array($decoded['items'] ?? null) ? $decoded['items'] : [];
            }
        }

        return [];
    }
}
