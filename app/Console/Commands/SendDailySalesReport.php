<?php

namespace App\Console\Commands;

use App\Models\DailySession;
use App\Models\Project;
use App\Services\DailySalesReportService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:send-daily-sales-report')]
#[Description('Send the daily sales report email for any project whose session has not been reported yet')]
class SendDailySalesReport extends Command
{
    public function handle(DailySalesReportService $service): void
    {
        $today = now()->toDateString();

        Project::where('is_active', true)->each(function (Project $project) use ($service, $today) {
            $session = DailySession::where('project_id', $project->id)
                ->whereDate('opened_at', $today)
                ->whereNull('report_sent_at')
                ->latest('opened_at')
                ->first();

            if ($session) {
                $service->sendFor($session);
            }
        });
    }
}
