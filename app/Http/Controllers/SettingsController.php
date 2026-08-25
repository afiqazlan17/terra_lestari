<?php

namespace App\Http\Controllers;

use App\Models\CapitalInjection;
use App\Models\DailySession;
use App\Models\Order;
use App\Models\OrderItem;
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
        return view('settings.index', [
            'user' => $request->user(),
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

    public function resetData(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperuser(), 403);

        $request->validate([
            'confirm' => ['required', 'in:RESET'],
        ]);

        $project = $request->user()->currentProject();

        DB::transaction(function () use ($project) {
            OrderItem::whereIn('order_id', Order::where('project_id', $project->id)->pluck('id'))->delete();
            Order::where('project_id', $project->id)->delete();
            Purchase::where('project_id', $project->id)->delete();
            CapitalInjection::where('project_id', $project->id)->delete();
            DailySession::where('project_id', $project->id)->delete();
        });

        return back()->with('success', 'Semua data transaksi (jualan, belian, modal, sesi harian) telah direset. Menu dan akaun staff dikekalkan.');
    }
}
