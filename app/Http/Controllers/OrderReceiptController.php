<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OrderReceiptController extends Controller
{
    public function show(Request $request, Order $order): View
    {
        abort_unless($order->project_id === $request->user()->currentProject()?->id, 403);

        $order->load(['items', 'cashier', 'project']);

        return view('pos.receipt', ['order' => $order]);
    }
}
