<?php

namespace App\Console\Commands;

use App\Models\DailySession;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:purge-test-session {session_id : The DailySession id to delete, along with its orders/order items} {--force : Skip the confirmation prompt}')]
#[Description('Deletes one DailySession and all its Orders/OrderItems - for cleanly removing a test session (open, sell, close) done on production without touching real data. Refuses to run if the session was not already closed, since that usually means it is the real, currently-active session.')]
class PurgeTestSession extends Command
{
    public function handle(): int
    {
        $session = DailySession::with('orders.items')->find($this->argument('session_id'));

        if (! $session) {
            $this->error('Sesi tidak dijumpai.');

            return self::FAILURE;
        }

        if ($session->status !== 'closed') {
            $this->error("Sesi #{$session->id} masih 'open' - bukan sesi test yang dah selesai. Tutup dulu (atau confirm ini memang session test) sebelum padam.");

            return self::FAILURE;
        }

        $orderCount = $session->orders->count();
        $itemCount = $session->orders->sum(fn ($order) => $order->items->count());

        $this->info("Sesi #{$session->id}: dibuka {$session->opened_at} oleh {$session->openedBy?->name}, ditutup {$session->closed_at}.");
        $this->info("Akan padam {$orderCount} order dan {$itemCount} order item bersama sesi ini.");

        if (! $this->option('force') && ! $this->confirm('Teruskan padam?', false)) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        foreach ($session->orders as $order) {
            $order->items()->delete();
            $order->delete();
        }
        $session->delete();

        $this->info('Sesi test dan semua order/item berkaitan telah dipadam.');

        return self::SUCCESS;
    }
}
