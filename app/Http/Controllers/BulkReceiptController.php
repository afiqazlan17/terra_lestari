<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\StoresReceipts;
use App\Models\Purchase;
use App\Rules\ValidReceiptFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BulkReceiptController extends Controller
{
    use StoresReceipts;

    public function create(Request $request): View
    {
        return view('receipts.bulk', [
            'project' => $request->user()->currentProject(),
        ]);
    }

    /**
     * Creates one Purchase (Belian or Perbelanjaan, decided per-row by
     * category) per included row. Rows are independent - one row failing
     * validation doesn't stop the others from saving.
     */
    public function store(Request $request): JsonResponse
    {
        $project = $request->user()->currentProject();

        $rows = $request->input('receipts', []);
        $files = $request->file('receipts', []);

        $created = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $validator = validator(
                [
                    'category' => $row['category'] ?? null,
                    'purchase_date' => $row['purchase_date'] ?? null,
                    'description' => $row['description'] ?? null,
                    'supplier_name' => $row['supplier_name'] ?? null,
                    'amount' => $row['amount'] ?? null,
                    'notes' => $row['notes'] ?? null,
                    'receipt' => $files[$index]['file'] ?? null,
                ],
                [
                    'category' => ['required', Rule::in(array_keys(Purchase::CATEGORIES))],
                    'purchase_date' => ['required', 'date'],
                    'description' => ['required', 'string', 'max:255'],
                    'supplier_name' => ['nullable', 'string', 'max:255'],
                    'amount' => ['required', 'numeric', 'min:0'],
                    'notes' => ['nullable', 'string', 'max:1000'],
                    'receipt' => ['nullable', 'file', 'max:8192', new ValidReceiptFile],
                ]
            );

            if ($validator->fails()) {
                $errors[] = ['index' => (int) $index, 'message' => $validator->errors()->first()];

                continue;
            }

            $validated = $validator->validated();

            $receiptPath = null;

            if (! empty($files[$index]['file'])) {
                $receiptPath = $this->storeReceipt($files[$index]['file'], 'receipts/'.$project->id);
            }

            $project->purchases()->create([
                'recorded_by' => $request->user()->id,
                'category' => $validated['category'],
                'purchase_date' => $validated['purchase_date'],
                'supplier_name' => $validated['supplier_name'] ?? null,
                'description' => $validated['description'],
                'amount' => $validated['amount'],
                'receipt_path' => $receiptPath,
                'notes' => $validated['notes'] ?? null,
            ]);

            $created++;
        }

        return response()->json(['created' => $created, 'errors' => $errors]);
    }
}
