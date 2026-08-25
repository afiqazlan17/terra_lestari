<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <title>Resit {{ $order->order_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            width: 300px;
            margin: 0 auto;
            padding: 16px;
            font-size: 13px;
            color: #111;
        }
        h1 { font-size: 16px; text-align: center; margin: 0 0 4px; }
        .center { text-align: center; }
        .muted { color: #555; font-size: 11px; }
        .divider { border-top: 1px dashed #999; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        .right { text-align: right; }
        .totals td { padding: 2px 0; }
        .grand { font-weight: bold; font-size: 14px; }
        .actions { margin-top: 16px; text-align: center; }
        .actions a, .actions button {
            display: inline-block; margin: 4px; padding: 8px 16px;
            background: #f59e0b; color: white; text-decoration: none;
            border-radius: 6px; border: none; font-size: 13px; cursor: pointer;
        }
        @media print {
            .actions { display: none; }
            body { width: auto; }
        }
    </style>
</head>
<body>
    <h1>SAJIAN BAGINDA</h1>
    <p class="center muted">Warisan Rasa Pantai Timur</p>
    <div class="divider"></div>
    <p class="muted">
        No. Resit: {{ $order->order_number }}<br>
        Tarikh: {{ $order->created_at->format('d/m/Y H:i') }}<br>
        Jenis: {{ $order->typeLabel() }}
    </p>
    <div class="divider"></div>
    <table>
        @foreach ($order->items as $item)
            <tr>
                <td>{{ $item->product_name }}<br><span class="muted">{{ $item->qty }} x RM {{ number_format($item->unit_price, 2) }}</span></td>
                <td class="right">RM {{ number_format($item->subtotal, 2) }}</td>
            </tr>
        @endforeach
    </table>
    <div class="divider"></div>
    <table class="totals">
        <tr><td>Subtotal</td><td class="right">RM {{ number_format($order->subtotal, 2) }}</td></tr>
        @if ($order->discount > 0)
            <tr><td>Diskaun</td><td class="right">RM {{ number_format($order->discount, 2) }}</td></tr>
        @endif
        <tr class="grand"><td>Jumlah</td><td class="right">RM {{ number_format($order->total, 2) }}</td></tr>
        <tr><td class="muted">Bayaran</td><td class="right muted">{{ $order->paymentMethodLabel() }}</td></tr>
    </table>
    <div class="divider"></div>
    <p class="center muted">Terima kasih!</p>

    <div class="actions">
        <button onclick="window.print()">Print</button>
        <a href="{{ route('pos.index') }}">Order Baru</a>
    </div>
</body>
</html>
