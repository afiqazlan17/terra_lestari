<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <title>Resit {{ $order->order_number }}</title>
    @vite(['resources/js/app.js'])
    @php
        $is58mm = ($order->project->receipt_paper_width ?? '80mm') === '58mm';
    @endphp
    <style>
        * { box-sizing: border-box; }
        body {
            background: #f3f4f6;
            margin: 0;
            padding: 32px 16px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .receipt {
            font-family: 'Courier New', monospace;
            width: 380px;
            max-width: 100%;
            margin: 0 auto;
            padding: 24px;
            font-size: 15px;
            color: #111;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0,0,0,.12);
        }
        h1 { font-size: 20px; text-align: center; margin: 0 0 4px; }
        .logo { display: block; max-width: 220px; width: 100%; height: auto; margin: 0 auto 8px; }
        .center { text-align: center; }
        .muted { color: #555; font-size: 13px; }
        .divider { border-top: 1px dashed #999; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 3px 0; vertical-align: top; }
        .right { text-align: right; white-space: nowrap; }
        .totals td { padding: 3px 0; }
        .grand { font-weight: bold; font-size: 17px; }
        .actions { margin-top: 20px; text-align: center; }
        .actions a, .actions button {
            display: inline-block; margin: 4px; padding: 8px 16px;
            background: #f59e0b; color: white; text-decoration: none;
            border-radius: 6px; border: none; font-size: 13px; cursor: pointer;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .actions .secondary { background: #fff; color: #6b7280; border: 1px solid #d1d5db; }
        @media print {
            body { background: #fff; padding: 0; }
            .receipt {
                width: {{ $is58mm ? '220px' : '300px' }};
                padding: {{ $is58mm ? '10px' : '16px' }};
                font-size: {{ $is58mm ? '11px' : '13px' }};
                border-radius: 0;
                box-shadow: none;
            }
            h1 { font-size: {{ $is58mm ? '14px' : '16px' }}; }
            .logo { max-width: {{ $is58mm ? '150px' : '200px' }}; }
            .muted { font-size: {{ $is58mm ? '9px' : '11px' }}; }
            .grand { font-size: {{ $is58mm ? '12px' : '14px' }}; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <img src="{{ asset('images/logo.png') }}" alt="Sajian Baginda" class="logo">
        @if ($order->isVoided())
            <p class="center" style="color: #b91c1c; font-weight: bold; margin: 6px 0;">*** DIBATALKAN (VOID) ***</p>
        @endif
        <div class="divider"></div>
        <p class="muted">
            No. Resit: {{ $order->order_number }}<br>
            Tarikh: {{ $order->created_at->format('d/m/Y H:i') }}
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
            @if ($order->cash_received !== null)
                <tr><td class="muted">Tunai Diterima</td><td class="right muted">RM {{ number_format($order->cash_received, 2) }}</td></tr>
                <tr><td class="muted">Baki</td><td class="right muted">RM {{ number_format(max($order->cash_received - $order->total, 0), 2) }}</td></tr>
            @endif
        </table>
        <div class="divider"></div>
        <p class="center muted">Terima kasih!</p>

        <div class="actions">
            <button onclick="sbPrintReceipt()">Print</button>
            <button class="secondary" onclick="sbGoBack()">Back</button>
        </div>
    </div>

    <script>
        const RECEIPT_DATA = {
            orderNumber: @json($order->order_number),
            dateStr: @json($order->created_at->format('d/m/Y H:i')),
            items: [
                @foreach ($order->items as $item)
                { name: @json($item->product_name), qty: {{ $item->qty }}, price: @json(number_format($item->unit_price, 2)), lineTotal: @json(number_format($item->subtotal, 2)) },
                @endforeach
            ],
            subtotal: @json(number_format($order->subtotal, 2)),
            discount: {{ $order->discount }},
            total: @json(number_format($order->total, 2)),
            paymentLabel: @json($order->paymentMethodLabel()),
            cashReceived: @json($order->cash_received !== null ? number_format($order->cash_received, 2) : null),
            cashChange: @json($order->cash_received !== null ? number_format(max($order->cash_received - $order->total, 0), 2) : null),
        };

        async function sbPrintReceipt() {
            if (window.SBPrinter && window.SBPrinter.isSupported()) {
                if (! window.SBPrinter.isConnected()) {
                    await window.SBPrinter.waitUntilReady(1500);
                }

                if (! window.SBPrinter.isConnected()) {
                    // Reconnecting silently didn't work (common on Android Chrome -
                    // persistent BLE reconnect across page loads isn't reliable).
                    // Re-request the device right here, while we still have the
                    // user gesture from this Print tap.
                    try {
                        await window.SBPrinter.connect();
                    } catch (e) {
                        console.error('Bluetooth connect gagal', e);
                        alert('Printer tidak connect. Sila pilih "RichTech" pada senarai yang keluar.');
                        return;
                    }
                }

                try {
                    const bytes = await window.buildReceiptEscPos(RECEIPT_DATA, {{ $is58mm ? 'true' : 'false' }});
                    await window.SBPrinter.write(bytes);
                    return;
                } catch (e) {
                    console.error('Bluetooth print gagal', e);
                    alert('Cetak gagal. Sila semak sambungan printer di Settings > Printer Resit (Bluetooth).');
                    return;
                }
            }

            window.print();
        }

        function sbGoBack() {
            if (window.history.length > 1) {
                window.history.back();
                return;
            }
            window.location.href = '{{ route('pos.index') }}';
        }
    </script>
</body>
</html>
