<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Rules\ValidReceiptFile;
use App\Services\ReceiptExtractionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReceiptExtractionController extends Controller
{
    public function extract(Request $request, ReceiptExtractionService $service): JsonResponse
    {
        $request->validate([
            'receipt' => ['required', 'file', 'max:8192', new ValidReceiptFile],
        ]);

        try {
            $data = $service->extract($request->file('receipt'));
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['error' => 'Gagal baca resit. Sila isi manual.'], 422);
        }

        $data['duplicate'] = $this->findLikelyDuplicate($request, $data);

        return response()->json($data);
    }

    /**
     * Warn (never block) when an existing, non-voided purchase already
     * matches this receipt's date + amount - most likely the same receipt
     * uploaded twice.
     */
    private function findLikelyDuplicate(Request $request, array $data): ?array
    {
        $project = $request->user()->currentProject();

        if (! $project || empty($data['purchase_date']) || empty($data['amount'])) {
            return null;
        }

        $match = Purchase::where('project_id', $project->id)
            ->whereNull('voided_at')
            ->whereDate('purchase_date', $data['purchase_date'])
            ->whereBetween('amount', [$data['amount'] - 0.01, $data['amount'] + 0.01])
            ->with('recordedBy')
            ->latest('created_at')
            ->first();

        if (! $match) {
            return null;
        }

        return [
            'description' => $match->description,
            'supplier_name' => $match->supplier_name,
            'recorded_by' => $match->recordedBy?->name,
            'recorded_at' => $match->created_at->format('d/m/Y H:i'),
        ];
    }
}
