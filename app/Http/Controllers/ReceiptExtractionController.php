<?php

namespace App\Http\Controllers;

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

        return response()->json($data);
    }
}
