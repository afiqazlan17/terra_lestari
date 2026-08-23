<?php

namespace App\Services;

use App\Mail\DailySalesReportMail;
use App\Models\DailySession;
use App\Models\Order;
use App\Models\Project;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class DailySalesReportService
{
    public function summaryFor(Project $project, Carbon $date): array
    {
        $sales = Order::where('project_id', $project->id)
            ->where('status', 'completed')
            ->whereDate('created_at', $date)
            ->sum('total');

        $orderCount = Order::where('project_id', $project->id)
            ->where('status', 'completed')
            ->whereDate('created_at', $date)
            ->count();

        $purchases = Purchase::where('project_id', $project->id)
            ->whereDate('purchase_date', $date)
            ->sum('amount');

        return [
            'sales' => (float) $sales,
            'orderCount' => $orderCount,
            'purchases' => (float) $purchases,
            'profit' => (float) $sales - (float) $purchases,
        ];
    }

    public function recipientsFor(Project $project): array
    {
        // Owner/superuser accounts are not pinned to a project (they fall
        // back to the first active project via User::currentProject()), so
        // both project-scoped and unscoped full-access users must be checked.
        return User::where('project_id', $project->id)
            ->orWhereNull('project_id')
            ->get()
            ->filter(fn (User $user) => $user->hasFullAccess() && $user->email)
            ->pluck('email')
            ->unique()
            ->values()
            ->all();
    }

    public function sendFor(DailySession $session): void
    {
        if ($session->report_sent_at) {
            return;
        }

        $project = $session->project;
        $date = $session->opened_at->copy();
        $summary = $this->summaryFor($project, $date);
        $recipients = $this->recipientsFor($project);

        if (empty($recipients)) {
            return;
        }

        Mail::to($recipients)->send(new DailySalesReportMail($project, $summary, $date));

        $session->update(['report_sent_at' => now()]);
    }
}
