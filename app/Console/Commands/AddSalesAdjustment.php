<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Project;
use App\Services\SalesAdjustmentService;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:add-sales-adjustment {date : Date of the already-closed daily session, e.g. 2026-09-02} {--cash=0} {--qr=0} {--card=0} {--reason=Tidak sempat direkod semasa sistem down} {--force : Skip the confirmation prompt}')]
#[Description('One-off: adds a manual "Pelarasan Jualan" order per payment method for a closed day when real sales could not be keyed in on time (e.g. an outage), so recorded totals catch up to the actual counted cash/QR figures. Does not touch any existing order. Safe to re-run - skips a payment method that already has an adjustment for that date. Same logic is available from the web UI at /jualan/pelarasan (superuser only).')]
class AddSalesAdjustment extends Command
{
    public function handle(SalesAdjustmentService $service): int
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

        $this->info("Tarikh: {$date->toDateString()}.");
        foreach ($amounts as $method => $amount) {
            $this->info('- '.Order::PAYMENT_METHODS[$method].': RM'.number_format($amount, 2));
        }

        if (! $this->option('force') && ! $this->confirm('Teruskan tambah pelarasan ini?', false)) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        try {
            $results = $service->apply($project, $date, $amounts, $this->option('reason'));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        foreach ($results as $method => $outcome) {
            $outcome === 'created'
                ? $this->info(Order::PAYMENT_METHODS[$method].': ditambah.')
                : $this->warn(Order::PAYMENT_METHODS[$method]." pada {$date->toDateString()} sudah wujud. Dilangkau.");
        }

        $this->info('Selesai.');

        return self::SUCCESS;
    }
}
