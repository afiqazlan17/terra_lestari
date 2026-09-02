<?php

namespace App\Console\Commands;

use App\Models\DailySession;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:add-sales-adjustment {date : Date of the already-closed daily session, e.g. 2026-09-02} {--cash=0} {--qr=0} {--card=0} {--reason=Tidak sempat direkod semasa sistem down} {--force : Skip the confirmation prompt}')]
#[Description('One-off: adds a manual "Pelarasan Jualan" order per payment method for a closed day when real sales could not be keyed in on time (e.g. an outage), so recorded totals catch up to the actual counted cash/QR figures. Does not touch any existing order. Safe to re-run - skips a payment method that already has an adjustment for that date.')]
class AddSalesAdjustment extends Command
{
    public function handle(): int
    {
        $date = Carbon::parse($this->argument('date'))->startOfDay();

        $amounts = array_filter([
            Order::PAYMENT_METHOD_CASH => (float) $this->option('cash'),
            Order::PAYMENT_METHOD_QR => (float) $this->option('qr'),
            Order::PAYMENT_METHOD_CARD => (float) $this->option('card'),
        ], fn ($amount) => $amount > 0);

        if (empty($amounts)) {
            $this->error('Sila bagi sekurang-kurangnya satu amaun (--cash, --qr, atau --card) lebih dari 0.');

            return self::FAILURE;
        }

        $project = Project::where('is_active', true)->orderBy('id')->first();

        if (! $project) {
            $this->error('No active project found.');

            return self::FAILURE;
        }

        $session = DailySession::where('project_id', $project->id)
            ->whereDate('opened_at', $date->toDateString())
            ->latest('opened_at')
            ->first();

        if (! $session) {
            $this->error("Tiada sesi harian dijumpai untuk {$date->toDateString()}.");

            return self::FAILURE;
        }

        $reason = $this->option('reason');
        $adjustmentLabel = "Pelarasan Jualan ({$reason})";
        $cashierId = $session->closed_by ?? $session->opened_by ?? User::where('role', User::ROLE_OWNER)->value('id');

        $this->info("Sesi #{$session->id} untuk {$date->toDateString()} (status: {$session->status}).");
        foreach ($amounts as $method => $amount) {
            $this->info('- '.Order::PAYMENT_METHODS[$method].': RM'.number_format($amount, 2));
        }

        if (! $this->option('force') && ! $this->confirm('Teruskan tambah pelarasan ini?', false)) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        foreach ($amounts as $method => $amount) {
            $alreadyExists = Order::where('project_id', $project->id)
                ->where('daily_session_id', $session->id)
                ->where('payment_method', $method)
                ->whereHas('items', fn ($q) => $q->where('product_name', 'like', 'Pelarasan Jualan%'))
                ->exists();

            if ($alreadyExists) {
                $this->warn("Pelarasan untuk {$method} pada {$date->toDateString()} sudah wujud. Dilangkau.");

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

                // Backdate to the session's own day so dashboard/report
                // date filters (whereDate) pick it up correctly.
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

                $this->info("Order {$order->order_number} ({$method}): RM".number_format($amount, 2).' ditambah.');
            });
        }

        $this->info('Selesai.');

        return self::SUCCESS;
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
