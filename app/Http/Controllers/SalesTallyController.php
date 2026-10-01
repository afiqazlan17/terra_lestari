<?php

namespace App\Http\Controllers;

use App\Models\DailySession;
use App\Models\Order;
use App\Models\Project;
use App\Services\SalesAdjustmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use RuntimeException;

/**
 * Per-day comparison of POS-recorded sales against what Tutup Hari counted,
 * with a one-click "Tallykan" that books any shortfall as an Adjustment
 * sale on that day - for days where sales happened but weren't all keyed
 * into the POS.
 */
class SalesTallyController extends Controller
{
    public const ADJUSTMENT_REASON = 'Adjustment';

    public function index(Request $request): View
    {
        abort_unless($request->user()->isSuperuser(), 403, 'Hanya Afiq/Amirul boleh akses Tally Jualan.');

        $project = $request->user()->currentProject();

        $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->subDays(30)->startOfDay();
        $to = $request->filled('to') ? Carbon::parse($request->input('to'))->endOfDay() : now()->endOfDay();

        $sessions = DailySession::where('project_id', $project->id)
            ->where('status', 'closed')
            ->whereBetween('opened_at', [$from, $to])
            ->latest('opened_at')
            ->get();

        $posSales = $this->posSalesByDay($project, $from, $to);
        $adjusted = Order::where('project_id', $project->id)
            ->whereIn('daily_session_id', $sessions->pluck('id'))
            ->whereHas('items', fn ($q) => $q->where('product_name', 'like', 'Pelarasan Jualan%'))
            ->pluck('daily_session_id')
            ->flip();

        $rows = $sessions->map(fn (DailySession $s) => [
            'session' => $s,
            'cashPos' => $posSales[$s->opened_at->toDateString().'|'.Order::PAYMENT_METHOD_CASH] ?? 0.0,
            'qrPos' => $posSales[$s->opened_at->toDateString().'|'.Order::PAYMENT_METHOD_QR] ?? 0.0,
            'hasAdjustment' => $adjusted->has($s->id),
        ]);

        return view('sales-tally.index', [
            'rows' => $rows,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function tally(Request $request, DailySession $dailySession, SalesAdjustmentService $service): RedirectResponse
    {
        abort_unless($request->user()->isSuperuser(), 403, 'Hanya Afiq/Amirul boleh akses Tally Jualan.');

        $project = $request->user()->currentProject();
        abort_unless($dailySession->project_id === $project->id, 403);
        abort_unless($dailySession->status === 'closed', 422, 'Hari ini belum ditutup.');

        $validated = $request->validate([
            'opening_cash' => ['required', 'numeric', 'min:0'],
        ]);

        // Opening cash on backfilled days was only a guess (previous day's
        // closing), so it's correctable here - closing cash minus it is
        // what the day's cash sales should have been.
        if ((float) $dailySession->opening_cash !== (float) $validated['opening_cash']) {
            $dailySession->update(['opening_cash' => $validated['opening_cash']]);
        }

        // Shortfalls are recomputed server-side rather than taken from the
        // form, so the adjustment always matches the stored figures.
        $date = $dailySession->opened_at->copy()->startOfDay();
        $posSales = $this->posSalesByDay($project, $date, $date->copy()->endOfDay());
        $key = $date->toDateString();

        $cashGap = round((float) $dailySession->closing_cash - (float) $dailySession->opening_cash
            - ($posSales[$key.'|'.Order::PAYMENT_METHOD_CASH] ?? 0), 2);
        $qrGap = round((float) $dailySession->closing_qr - ($posSales[$key.'|'.Order::PAYMENT_METHOD_QR] ?? 0), 2);

        $amounts = array_filter([
            Order::PAYMENT_METHOD_CASH => $cashGap,
            Order::PAYMENT_METHOD_QR => $qrGap,
        ], fn ($gap) => $gap > 0);

        $dayLabel = $date->translatedFormat('d F Y');

        if (! $amounts) {
            return back()->with('success', "{$dayLabel}: tiada jualan nak ditambah.");
        }

        try {
            $results = $service->apply($project, $date, $amounts, self::ADJUSTMENT_REASON, $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $summary = collect($results)->map(fn ($outcome, $method) => $outcome === 'created'
            ? Order::PAYMENT_METHODS[$method].' +RM '.number_format($amounts[$method], 2)
            : Order::PAYMENT_METHODS[$method].' sudah ada adjustment, dilangkau'
        )->implode(' · ');

        return back()->with('success', "{$dayLabel} ditally: {$summary}");
    }

    /**
     * Opening cash on backfilled days was guessed from the previous day's
     * closing; set it in one go for every backfilled day in the range.
     * Days opened normally keep their real Buka Hari figure.
     */
    public function setBackfilledOpening(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperuser(), 403, 'Hanya Afiq/Amirul boleh akses Tally Jualan.');

        $validated = $request->validate([
            'opening_cash' => ['required', 'numeric', 'min:0'],
            'from' => ['required', 'date'],
            'to' => ['required', 'date'],
        ]);

        $updated = DailySession::where('project_id', $request->user()->currentProject()->id)
            ->where('status', 'closed')
            ->where('notes', 'like', '%'.DailySession::BACKFILL_NOTE.'%')
            ->whereBetween('opened_at', [Carbon::parse($validated['from'])->startOfDay(), Carbon::parse($validated['to'])->endOfDay()])
            ->update(['opening_cash' => $validated['opening_cash']]);

        return back()->with('success', "Duit Buka RM ".number_format($validated['opening_cash'], 2)." diset untuk {$updated} hari yang diisi semula.");
    }

    /** @return \Illuminate\Support\Collection<string, float> keyed "Y-m-d|method" */
    private function posSalesByDay(Project $project, Carbon $from, Carbon $to)
    {
        // Grouped in PHP since DATE() differs between MySQL and SQLite.
        return Order::where('project_id', $project->id)
            ->where('status', Order::STATUS_COMPLETED)
            ->whereBetween('created_at', [$from, $to])
            ->get(['created_at', 'payment_method', 'total'])
            ->groupBy(fn ($o) => $o->created_at->toDateString().'|'.$o->payment_method)
            ->map(fn ($group) => (float) $group->sum('total'));
    }
}
