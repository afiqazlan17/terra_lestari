<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\SalesAdjustmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use RuntimeException;

class SalesAdjustmentController extends Controller
{
    public function create(Request $request): View
    {
        abort_unless($request->user()->isSuperuser(), 403, 'Hanya Afiq/Amirul boleh akses Pelarasan Jualan.');

        $project = $request->user()->currentProject();

        $history = Order::where('project_id', $project->id)
            ->whereHas('items', fn ($q) => $q->where('product_name', 'like', 'Pelarasan Jualan%'))
            ->with('items')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        return view('sales-adjustments.create', [
            'history' => $history,
        ]);
    }

    public function store(Request $request, SalesAdjustmentService $service): RedirectResponse
    {
        abort_unless($request->user()->isSuperuser(), 403, 'Hanya Afiq/Amirul boleh akses Pelarasan Jualan.');

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'cash' => ['nullable', 'numeric', 'min:0'],
            'qr' => ['nullable', 'numeric', 'min:0'],
            'card' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $amounts = array_filter([
            Order::PAYMENT_METHOD_CASH => (float) ($validated['cash'] ?? 0),
            Order::PAYMENT_METHOD_QR => (float) ($validated['qr'] ?? 0),
            Order::PAYMENT_METHOD_CARD => (float) ($validated['card'] ?? 0),
        ], fn ($amount) => $amount > 0);

        if (empty($amounts)) {
            return back()->withInput()->with('error', 'Sila isi sekurang-kurangnya satu jumlah (Tunai, QR, atau Kad).');
        }

        $project = $request->user()->currentProject();

        try {
            $results = $service->apply(
                $project,
                Carbon::parse($validated['date']),
                $amounts,
                $validated['reason'],
                $request->user()->id
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $summary = collect($results)->map(
            fn ($outcome, $method) => Order::PAYMENT_METHODS[$method].': '.($outcome === 'created' ? 'ditambah' : 'sudah wujud, dilangkau')
        )->implode(' · ');

        return redirect()->route('sales-adjustments.create')->with('success', $summary);
    }
}
