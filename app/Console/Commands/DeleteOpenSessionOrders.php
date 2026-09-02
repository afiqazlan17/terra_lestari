<?php

namespace App\Console\Commands;

use App\Models\DailySession;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Hard-deletes every order under the currently open daily session(s) - for
 * wiping a session that only ever held test/junk orders (e.g. reopened
 * after hours to test a feature) before closing it for real. The session
 * itself is left open so Tutup Hari can still be done normally afterward,
 * with a real closing cash count.
 *
 * Always run without --force first to review exactly what would be deleted.
 */
class DeleteOpenSessionOrders extends Command
{
    protected $signature = 'app:delete-open-session-orders {--force : Actually delete - omit to preview only}';

    protected $description = 'Hard-delete all orders under the currently open daily session (preview by default, use --force to actually delete)';

    public function handle(): int
    {
        $sessions = DailySession::with('project')->where('status', 'open')->get();

        if ($sessions->isEmpty()) {
            $this->warn('Tiada sesi hari yang sedang open.');

            return self::SUCCESS;
        }

        $totalDeleted = 0;

        foreach ($sessions as $session) {
            $orders = Order::where('daily_session_id', $session->id)
                ->orderBy('order_number')
                ->get();

            $this->info("Sesi #{$session->id} ({$session->project->name}) - dibuka {$session->opened_at->format('d/m/Y H:i')}, {$orders->count()} order:");

            if ($orders->isEmpty()) {
                $this->line('  (tiada order)');

                continue;
            }

            $this->table(
                ['Order No', 'Jumlah (RM)', 'Kaedah Bayar', 'Status', 'Tarikh/Masa'],
                $orders->map(fn (Order $o) => [
                    $o->order_number,
                    number_format($o->total, 2),
                    $o->paymentMethodLabel(),
                    $o->status,
                    $o->created_at->format('d/m/Y H:i'),
                ])
            );

            if (! $this->option('force')) {
                continue;
            }

            DB::transaction(function () use ($orders) {
                foreach ($orders as $order) {
                    $order->delete();
                }
            });

            $totalDeleted += $orders->count();
            $this->info("  -> {$orders->count()} order dipadam terus.");
        }

        if (! $this->option('force')) {
            $this->info('Preview sahaja - tiada apa dipadam. Jalankan semula dengan --force untuk padam betul-betul.');
        } else {
            $this->info("Selesai. Jumlah {$totalDeleted} order dipadam. Sesi hari masih open - boleh Tutup Hari seperti biasa.");
        }

        return self::SUCCESS;
    }
}
