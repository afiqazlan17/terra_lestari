<?php

namespace App\Http\Controllers;

use App\Models\CapitalInjection;
use App\Models\DailySession;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Project;
use App\Models\Purchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        $counts = [];

        if ($request->user()->isSuperuser()) {
            $project = $request->user()->currentProject();

            $counts = [
                'orders' => Order::where('project_id', $project->id)->count(),
                'belian' => Purchase::where('project_id', $project->id)->where('category', Purchase::CATEGORY_BAHAN_MENTAH)->count(),
                'perbelanjaan' => Purchase::where('project_id', $project->id)->where('category', '!=', Purchase::CATEGORY_BAHAN_MENTAH)->count(),
                'capital_injections' => CapitalInjection::where('project_id', $project->id)->count(),
                'daily_sessions' => DailySession::where('project_id', $project->id)->count(),
            ];
        }

        return view('settings.index', [
            'user' => $request->user(),
            'counts' => $counts,
            'project' => $request->user()->currentProject(),
        ]);
    }

    public function updateLocale(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', Rule::in(['ms', 'en'])],
        ]);

        $request->user()->update(['locale' => $validated['locale']]);

        return back()->with('success', 'Bahasa dikemaskini.');
    }

    public function updatePaperWidth(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canManageOperations(), 403);

        $validated = $request->validate([
            'receipt_paper_width' => ['required', Rule::in([Project::PAPER_WIDTH_58MM, Project::PAPER_WIDTH_80MM])],
        ]);

        $request->user()->currentProject()->update(['receipt_paper_width' => $validated['receipt_paper_width']]);

        return back()->with('success', 'Lebar kertas resit dikemaskini.');
    }

    public function resetData(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperuser(), 403);

        $validated = $request->validate([
            'confirm' => ['required', 'in:RESET'],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['in:orders,belian,perbelanjaan,capital_injections,daily_sessions'],
        ]);

        $project = $request->user()->currentProject();
        $selected = $validated['categories'];
        $labels = [];

        DB::transaction(function () use ($project, $selected, &$labels) {
            if (in_array('orders', $selected, true)) {
                OrderItem::whereIn('order_id', Order::where('project_id', $project->id)->pluck('id'))->delete();
                Order::where('project_id', $project->id)->delete();
                $labels[] = 'Jualan';
            }

            if (in_array('belian', $selected, true)) {
                Purchase::where('project_id', $project->id)->where('category', Purchase::CATEGORY_BAHAN_MENTAH)->delete();
                $labels[] = 'Belian';
            }

            if (in_array('perbelanjaan', $selected, true)) {
                Purchase::where('project_id', $project->id)->where('category', '!=', Purchase::CATEGORY_BAHAN_MENTAH)->delete();
                $labels[] = 'Perbelanjaan';
            }

            if (in_array('capital_injections', $selected, true)) {
                CapitalInjection::where('project_id', $project->id)->delete();
                $labels[] = 'Modal Awal';
            }

            if (in_array('daily_sessions', $selected, true)) {
                DailySession::where('project_id', $project->id)->delete();
                $labels[] = 'Sesi Harian';
            }
        });

        return back()->with('success', 'Data berikut telah direset: '.implode(', ', $labels).'. Data lain dan akaun staff dikekalkan.');
    }
}
