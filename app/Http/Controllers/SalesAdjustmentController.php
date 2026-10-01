<?php

namespace App\Http\Controllers;

use App\Models\DailySession;
use App\Models\Order;
use App\Models\Project;
use App\Services\SalesAdjustmentService;
use App\Services\SalesSummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use RuntimeException;

class SalesAdjustmentController extends Controller
{
    public function create(Request $request, SalesSummaryService $summaryService): View
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
            'backfilled' => $this->unconfirmedBackfilledDays($project, $summaryService),
        ]);
    }

    /**
     * Bulk-record sales for days whose Tutup Hari was filled in after the
     * fact. A blank row is left for later; 0 marks the day done with no
     * sales added.
     */
    public function storeBackfilled(Request $request, SalesAdjustmentService $service, SalesSummaryService $summaryService): RedirectResponse
    {
        abort_unless($request->user()->isSuperuser(), 403, 'Hanya Afiq/Amirul boleh akses Pelarasan Jualan.');

        $validated = $request->validate([
            'entries' => ['required', 'array'],
            'entries.*.opening' => ['nullable', 'numeric', 'min:0'],
            'entries.*.cash' => ['nullable', 'numeric', 'min:0'],
            'entries.*.qr' => ['nullable', 'numeric', 'min:0'],
        ]);

        $project = $request->user()->currentProject();
        $done = 0;

        // Days whose sales were already keyed into the POS only lacked the
        // Tutup Hari record - adding the suggested figures would double up.
        if ($request->input('mode') === 'dismiss') {
            foreach ($this->unconfirmedBackfilledDays($project, $summaryService) as $day) {
                $day['session']->update([
                    'notes' => trim($day['session']->notes.' '.DailySession::SALES_CONFIRMED_NOTE),
                ]);
                $done++;
            }

            return redirect()->route('sales-adjustments.create')
                ->with('success', "{$done} hari ditanda selesai - tiada jualan ditambah.");
        }

        // Iterate the server-side list, not the submitted keys, so only
        // genuinely backfilled sessions can be touched.
        foreach ($this->unconfirmedBackfilledDays($project, $summaryService) as $day) {
            $entry = $validated['entries'][$day['session']->id] ?? null;

            if (! $entry || (($entry['cash'] ?? null) === null && ($entry['qr'] ?? null) === null)) {
                continue;
            }

            $amounts = array_filter([
                Order::PAYMENT_METHOD_CASH => (float) ($entry['cash'] ?? 0),
                Order::PAYMENT_METHOD_QR => (float) ($entry['qr'] ?? 0),
            ], fn ($amount) => $amount > 0);

            if ($amounts) {
                $service->apply($project, $day['date'], $amounts, 'Tutup Hari diisi semula', $request->user()->id);
            }

            // The backfill form auto-filled opening cash from the previous
            // day's closing, which is wrong when the till is emptied daily -
            // keep the corrected figure so the day's Tutup Hari report
            // (Jangkaan/Beza) matches the sales just recorded.
            $day['session']->update([
                'notes' => trim($day['session']->notes.' '.DailySession::SALES_CONFIRMED_NOTE),
                ...(isset($entry['opening']) ? ['opening_cash' => $entry['opening']] : []),
            ]);

            $done++;
        }

        return redirect()->route('sales-adjustments.create')
            ->with($done ? 'success' : 'error', $done
                ? "Jualan untuk {$done} hari disahkan."
                : 'Tiada hari disahkan - isi sekurang-kurangnya satu baris.');
    }

    /**
     * Backfilled sessions whose sales haven't been confirmed yet, with a
     * suggested sales figure per method: what the Tutup Hari counts imply
     * was taken, minus whatever the POS already recorded that day.
     */
    private function unconfirmedBackfilledDays(Project $project, SalesSummaryService $summaryService)
    {
        return DailySession::where('project_id', $project->id)
            ->where('status', 'closed')
            ->where('notes', 'like', '%'.DailySession::BACKFILL_NOTE.'%')
            ->where('notes', 'not like', '%'.DailySession::SALES_CONFIRMED_NOTE.'%')
            ->orderBy('opened_at')
            ->get()
            ->map(function (DailySession $session) use ($project, $summaryService) {
                $date = $session->opened_at->copy()->startOfDay();
                $summary = $summaryService->summaryFor($project, $date, $date);

                return [
                    'session' => $session,
                    'date' => $date,
                    'cashSales' => $summary['cashSales'],
                    'qrSales' => $summary['qrSales'],
                    'suggestedCash' => max(0, round((float) $session->closing_cash - (float) $session->opening_cash - $summary['cashSales'], 2)),
                    'suggestedQr' => max(0, round((float) $session->closing_qr - $summary['qrSales'], 2)),
                ];
            });
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
