<?php

namespace App\Services;

use Anthropic\Client;
use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Uses Claude vision to pull line items (product name, quantity, and
 * per-unit price) and the invoice's own date off a photographed or scanned
 * NBK vendor invoice, so staff can snap/upload the invoice instead of
 * typing every quantity - and the order date - into the order form by
 * hand. The invoice is the source of truth for price: the caller syncs our
 * NBK catalog's cost to whatever's printed here, since NBK's own prices
 * are what actually got paid, not our (possibly stale) catalog record.
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
        - price: the PER-UNIT price for that line (not the subtotal/line total), as a
          number. Most invoices print a "Harga (RM)" column - use that value directly.
          If only a line subtotal is printed with no per-unit price, divide subtotal by
          qty. If no price is readable for a line, return null for that line's price.

        Ignore invoice-level subtotals/totals and any non-item lines (headers,
        addresses, signatures, terms). If the image has no readable line items at all,
        return an empty items array.

        Also find the invoice's own date field (printed as "Tarikh" on these invoices,
        usually DD-MM-YYYY - day first, never the US month-first format). Return it as
        invoice_date in ISO format (YYYY-MM-DD). If no date is printed or readable, return
        null for invoice_date - do not guess it. Today's date is {today} (Malaysia time) -
        if the printed date is genuinely ambiguous, prefer whichever reading is closer to
        today rather than one many months away.
        TEXT;

    private function prompt(): string
    {
        return str_replace('{today}', now()->translatedFormat('d F Y'), self::PROMPT);
    }

    /** @return array{items: array, invoice_date: ?string} */
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
                'content' => [$contentBlock, ['type' => 'text', 'text' => $this->prompt()]],
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
                                        'price' => ['type' => ['number', 'null']],
                                    ],
                                    'required' => ['name', 'qty', 'price'],
                                    'additionalProperties' => false,
                                ],
                            ],
                            'invoice_date' => ['type' => ['string', 'null']],
                        ],
                        'required' => ['items', 'invoice_date'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
        );

        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $decoded = json_decode($block->text, true);

                return [
                    'items' => is_array($decoded['items'] ?? null) ? $decoded['items'] : [],
                    'invoice_date' => is_string($decoded['invoice_date'] ?? null) ? $decoded['invoice_date'] : null,
                ];
            }
        }

        return ['items' => [], 'invoice_date' => null];
    }
}
