<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Hard-deletes specific orders by order number - e.g. junk test orders
 * created while testing a feature in production, which void() would only
 * soft-cancel (kept, crossed out, excluded from totals) rather than remove
 * outright. Order items cascade-delete with their order automatically.
 *
 * Always run without --force first to review the exact matches before
 * anything is deleted.
 */
class DeleteOrders extends Command
{
    protected $signature = 'app:delete-orders {order_numbers* : Order numbers to delete, e.g. SB087 SB088} {--force : Actually delete - omit to preview only}';

    protected $description = 'Hard-delete specific orders by order number (preview by default, use --force to actually delete)';

    public function handle(): int
    {
        $orderNumbers = collect($this->argument('order_numbers'))->map(fn ($n) => strtoupper($n))->all();

        $orders = Order::with('project')
            ->whereIn('order_number', $orderNumbers)
            ->orderBy('order_number')
            ->get();

        $found = $orders->pluck('order_number')->all();
        $missing = array_diff($orderNumbers, $found);

        if ($orders->isEmpty()) {
            $this->warn('Tiada order dijumpai untuk nombor yang diberi.');

            return self::SUCCESS;
        }

        $this->table(
            ['Order No', 'Projek', 'Jumlah (RM)', 'Kaedah Bayar', 'Status', 'Tarikh/Masa'],
            $orders->map(fn (Order $o) => [
                $o->order_number,
                $o->project->name,
                number_format($o->total, 2),
                $o->paymentMethodLabel(),
                $o->status,
                $o->created_at->format('d/m/Y H:i'),
            ])
        );

        if (! empty($missing)) {
            $this->warn('Tidak dijumpai: '.implode(', ', $missing));
        }

        if (! $this->option('force')) {
            $this->info('Preview sahaja - tiada apa dipadam. Jalankan semula dengan --force untuk padam betul-betul.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($orders) {
            foreach ($orders as $order) {
                $order->delete();
            }
        });

        $this->info(count($orders).' order dipadam terus (order items turut dipadam).');

        return self::SUCCESS;
    }
}
