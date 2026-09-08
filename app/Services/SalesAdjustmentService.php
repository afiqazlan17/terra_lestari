<?php

namespace App\Services;

use App\Models\DailySession;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Adds a manual "Pelarasan Jualan" order per payment method for an
 * already-closed daily session, when real sales couldn't be keyed in on
 * time (e.g. a system outage) - so recorded totals (and the day's Jangkaan)
 * catch up to the actual counted cash/QR figures already locked in at
 * Tutup Hari. Never touches any existing order. Idempotent per session +
 * payment method - re-running with the same date/method is a no-op.
 */
class SalesAdjustmentService
{
    /**
     * @param  array<string, float>  $amounts  payment_method => amount (only entries > 0 are applied)
     * @return array<string, string> payment_method => 'created'|'skipped'
     */
    public function apply(Project $project, Carbon $date, array $amounts, string $reason, ?int $recordedByUserId = null): array
    {
        $session = DailySession::where('project_id', $project->id)
            ->whereDate('opened_at', $date->toDateString())
            ->latest('opened_at')
            ->first();

        if (! $session) {
            throw new RuntimeException("Tiada sesi harian dijumpai untuk {$date->toDateString()}.");
        }

        if ($session->status !== 'closed') {
            throw new RuntimeException("Sesi untuk {$date->toDateString()} masih belum ditutup - key-in jualan biasa terus dalam POS, bukan guna pelarasan.");
        }

        $adjustmentLabel = "Pelarasan Jualan ({$reason})";
        $cashierId = $recordedByUserId
            ?? $session->closed_by
            ?? $session->opened_by
            ?? User::where('role', User::ROLE_OWNER)->value('id');

        $results = [];

        foreach ($amounts as $method => $amount) {
            if ($amount <= 0) {
                continue;
            }

            $alreadyExists = Order::where('project_id', $project->id)
                ->where('daily_session_id', $session->id)
                ->where('payment_method', $method)
                ->whereHas('items', fn ($q) => $q->where('product_name', 'like', 'Pelarasan Jualan%'))
                ->exists();

            if ($alreadyExists) {
                $results[$method] = 'skipped';

                continue;
            }

            DB::transaction(function () use ($project, $session, $method, $amount, $adjustmentLabel, $cashierId, $date) {
                $order = Order::create([
                    'project_id' => $project->id,
                    'daily_session_id' => $session->id,
                    'cashier_id' => $cashierId,
                    'order_number' => $this->generateOrderNumber($project->id),
                    'subtotal' => $amount,
                    'discount' => 0,
                    'total' => $amount,
                    'payment_method' => $method,
                    'cash_received' => $method === Order::PAYMENT_METHOD_CASH ? $amount : null,
                    'order_type' => Order::TYPE_DINE_IN,
                    'status' => Order::STATUS_COMPLETED,
                ]);

                // Backdate to the session's own day so dashboard/report date
                // filters (whereDate) pick it up correctly.
                $order->timestamps = false;
                $order->created_at = $date->copy()->setTime(23, 58, 0);
                $order->updated_at = $order->created_at;
                $order->save();

                $order->items()->create([
                    'product_id' => null,
                    'product_name' => $adjustmentLabel,
                    'unit_price' => $amount,
                    'qty' => 1,
                    'subtotal' => $amount,
                ]);
            });

            $results[$method] = 'created';
        }

        return $results;
    }

    /**
     * Matches PosController::generateOrderNumber() - order_number is
     * globally unique, so derive the next one from the highest existing
     * suffix rather than a per-day count.
     */
    private function generateOrderNumber(int $projectId): string
    {
        $maxNumber = Order::where('project_id', $projectId)
            ->where('order_number', 'like', 'SB%')
            ->pluck('order_number')
            ->map(fn ($number) => (int) substr($number, 2))
            ->max();

        return sprintf('SB%03d', ($maxNumber ?? 0) + 1);
    }
}
