<?php

namespace App\Services;

use App\Mail\DailySalesReportMail;
use App\Models\DailySession;
use App\Models\Order;
use App\Models\OrderItem;
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

        $topItemRow = OrderItem::whereHas('order', function ($query) use ($project, $date) {
            $query->where('project_id', $project->id)
                ->where('status', 'completed')
                ->whereDate('created_at', $date);
        })
            ->selectRaw('product_name, SUM(qty) as qty_sold')
            ->groupBy('product_name')
            ->orderByDesc('qty_sold')
            ->first();

        // Grouped in PHP rather than via a DB HOUR() function, since that
        // function's name differs between MySQL (production) and SQLite
        // (local/test) - the row volume for one outlet's single day is
        // small enough that this is not a performance concern.
        $peakHour = Order::where('project_id', $project->id)
            ->where('status', 'completed')
            ->whereDate('created_at', $date)
            ->get(['created_at'])
            ->groupBy(fn ($order) => $order->created_at->format('H'))
            ->sortByDesc(fn ($group) => $group->count())
            ->keys()
            ->first();

        return [
            'sales' => (float) $sales,
            'orderCount' => $orderCount,
            'purchases' => (float) $purchases,
            'profit' => (float) $sales - (float) $purchases,
            'topItem' => $topItemRow ? "{$topItemRow->product_name} ({$topItemRow->qty_sold} unit)" : null,
            'peakHour' => $peakHour !== null ? sprintf('%02d:00 - %02d:00', (int) $peakHour, ((int) $peakHour + 1) % 24) : null,
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
