<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <title>Laporan Tutup Hari - {{ $date->translatedFormat('d F Y') }}</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap">
    <style>
        :root {
            --bg: #efece7;
            --paper: #fffdfa;
            --ink: #241b16;
            --ink-muted: #6b5d53;
            --ink-faint: #a3958a;
            --rule: #e3dbd0;
            --accent: #8a2e28;
            --accent-soft: #f6e9e7;
            --good: #2f6d4f;
            --good-soft: #e9f3ee;
            --bad: #a13a2c;
            --bad-soft: #faeae7;
        }
        * { box-sizing: border-box; }
        body {
            background: var(--bg);
            color: var(--ink);
            font-family: 'Figtree', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
            margin: 0;
            padding: 32px 16px 60px;
        }
        .toolbar {
            max-width: 760px;
            margin: 0 auto 16px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        .toolbar a, .toolbar button {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid var(--rule);
            background: var(--paper);
            color: var(--ink-muted);
        }
        .toolbar .primary { background: var(--accent); color: #fff; border-color: var(--accent); }
        .page {
            max-width: 760px;
            margin: 0 auto;
            background: var(--paper);
            border: 1px solid var(--rule);
            border-radius: 4px;
            padding: 44px 48px 40px;
            box-shadow: 0 1px 3px rgba(30, 20, 15, 0.06);
        }
        header.doc {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--ink);
            margin-bottom: 24px;
        }
        header.doc .brand { font-size: 19px; font-weight: 700; letter-spacing: -0.01em; }
        header.doc .brand small {
            display: block; font-size: 11px; font-weight: 500; color: var(--ink-faint);
            letter-spacing: 0.04em; text-transform: uppercase; margin-top: 2px;
        }
        header.doc .doc-title { text-align: right; font-size: 11px; color: var(--ink-muted); line-height: 1.6; }
        header.doc .doc-title strong { display: block; font-size: 14px; color: var(--ink); font-weight: 700; margin-bottom: 2px; }
        .session-line {
            display: flex; justify-content: space-between; font-size: 12.5px; color: var(--ink-muted);
            margin-bottom: 28px; flex-wrap: wrap; gap: 6px 18px;
        }
        .session-line span strong { color: var(--ink); font-weight: 600; }
        .stat-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 28px; }
        .stat { background: var(--accent-soft); border-radius: 6px; padding: 14px 16px; }
        .stat .label { font-size: 10.5px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--accent); }
        .stat .value { font-size: 22px; font-weight: 700; margin-top: 4px; font-variant-numeric: tabular-nums; letter-spacing: -0.01em; }
        section { margin-bottom: 26px; }
        section h2 {
            font-size: 12px; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase;
            color: var(--ink-faint); margin: 0 0 10px; padding-bottom: 6px; border-bottom: 1px solid var(--rule);
        }
        table.report { width: 100%; border-collapse: collapse; font-size: 13px; }
        table.report td, table.report th { padding: 7px 0; text-align: left; }
        table.report th {
            font-weight: 600; color: var(--ink-muted); font-size: 11px; text-transform: uppercase;
            letter-spacing: 0.03em; border-bottom: 1px solid var(--rule);
        }
        table.report td.num, table.report th.num { text-align: right; font-variant-numeric: tabular-nums; }
        table.report tbody tr:not(:last-child) td { border-bottom: 1px solid var(--rule); }
        table.report tfoot td { padding-top: 10px; font-weight: 700; border-top: 2px solid var(--ink); }
        .split-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .cash-box { border: 1px solid var(--rule); border-radius: 6px; overflow: hidden; }
        .cash-row { display: flex; justify-content: space-between; padding: 10px 14px; font-size: 13px; border-bottom: 1px solid var(--rule); }
        .cash-row .amt { font-variant-numeric: tabular-nums; font-weight: 600; }
        .cash-row.total { background: var(--accent-soft); font-weight: 700; }
        .cash-row.variance.good { background: var(--good-soft); color: var(--good); }
        .cash-row.variance.bad { background: var(--bad-soft); color: var(--bad); }
        .cash-row:last-child { border-bottom: none; }
        .pill-list { display: flex; flex-direction: column; gap: 8px; }
        .pill-row { display: flex; justify-content: space-between; align-items: center; font-size: 13px; padding: 8px 12px; background: var(--accent-soft); border-radius: 6px; }
        .pill-row .rank { font-size: 11px; font-weight: 700; color: var(--accent); width: 20px; }
        .pill-row .name { flex: 1; margin: 0 8px; }
        .pill-row .qty { font-variant-numeric: tabular-nums; color: var(--ink-muted); font-size: 12px; }
        .void-note { font-size: 12.5px; color: var(--bad); background: var(--bad-soft); border-radius: 6px; padding: 10px 14px; }
        .void-note.empty { color: var(--ink-faint); background: transparent; border: 1px dashed var(--rule); }
        footer.doc { margin-top: 30px; padding-top: 16px; border-top: 1px solid var(--rule); font-size: 11px; color: var(--ink-faint); display: flex; justify-content: space-between; }
        p.empty { font-size: 13px; color: var(--ink-faint); }

        @media print {
            body { background: #fff; padding: 0; font-size: 12px; }
            .toolbar { display: none; }
            .page { max-width: none; width: 190mm; margin: 0 auto; padding: 0; border: none; box-shadow: none; border-radius: 0; }
            @page { size: A4; margin: 10mm; }
            header.doc { padding-bottom: 12px; margin-bottom: 14px; }
            header.doc .brand { font-size: 16px; }
            header.doc .doc-title strong { font-size: 12.5px; }
            .session-line { margin-bottom: 16px; }
            .stat-row { margin-bottom: 16px; gap: 8px; }
            .stat { padding: 10px 12px; }
            .stat .value { font-size: 18px; }
            section { margin-bottom: 12px; }
            section h2 { margin-bottom: 6px; padding-bottom: 4px; }
            table.report td, table.report th { padding: 3px 0; }
            table.report tfoot td { padding-top: 5px; }
            .cash-row { padding: 5px 12px; }
            .pill-list { gap: 4px; }
            .pill-row { padding: 4px 10px; }
            footer.doc { margin-top: 8px; padding-top: 6px; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('dashboard') }}">Kembali</a>
        <button type="button" class="primary" onclick="window.print()">Print / Simpan PDF</button>
    </div>

    <div class="page">
        <header class="doc">
            <div class="brand">{{ $session->project->name }}<small>Laporan Tutup Hari</small></div>
            <div class="doc-title">
                <strong>{{ $date->translatedFormat('l, d F Y') }}</strong>
            </div>
        </header>

        <div class="session-line">
            <span>Dibuka: <strong>{{ $session->opened_at->format('H:i A') }}</strong> oleh <strong>{{ $session->openedBy->name }}</strong></span>
            <span>
                @if ($session->closed_at)
                    Ditutup: <strong>{{ $session->closed_at->format('H:i A') }}</strong> oleh <strong>{{ $session->closedBy->name }}</strong>
                @else
                    <span style="color: var(--bad);">Sesi masih dibuka</span>
                @endif
            </span>
        </div>

        <div class="stat-row">
            <div class="stat">
                <div class="label">Jumlah Jualan</div>
                <div class="value">RM {{ number_format($summary['totalSales'], 2) }}</div>
            </div>
            <div class="stat">
                <div class="label">Transaksi</div>
                <div class="value">{{ $summary['orderCount'] }}</div>
            </div>
            <div class="stat">
                <div class="label">Item Terjual</div>
                <div class="value">{{ $summary['itemCount'] }}</div>
            </div>
        </div>

        <section>
            <h2>Jualan ikut Kaedah Bayaran</h2>
            <table class="report">
                <thead><tr><th>Kaedah</th><th class="num">Jumlah</th></tr></thead>
                <tbody>
                    <tr><td>Cash</td><td class="num">RM {{ number_format($summary['cashSales'], 2) }}</td></tr>
                    <tr><td>QR / DuitNow</td><td class="num">RM {{ number_format($summary['qrSales'], 2) }}</td></tr>
                    @if ($summary['cardSales'] > 0)
                        <tr><td>Kad</td><td class="num">RM {{ number_format($summary['cardSales'], 2) }}</td></tr>
                    @endif
                </tbody>
                <tfoot><tr><td>Jumlah</td><td class="num">RM {{ number_format($summary['totalSales'], 2) }}</td></tr></tfoot>
            </table>
        </section>

        <section>
            <h2>Jualan ikut Sumber</h2>
            <table class="report">
                <thead><tr><th>Sumber</th><th class="num">Jumlah</th></tr></thead>
                <tbody>
                    <tr><td>Sajian Baginda (SB)</td><td class="num">RM {{ number_format($summary['sbSales'], 2) }}</td></tr>
                    <tr><td>Fresh From Kelantan (NBK)</td><td class="num">RM {{ number_format($summary['nbkSales'], 2) }}</td></tr>
                </tbody>
                <tfoot><tr><td>Jumlah</td><td class="num">RM {{ number_format($summary['totalSales'], 2) }}</td></tr></tfoot>
            </table>
        </section>

        <div class="split-cols">
            <section>
                <h2>Tunai</h2>
                @if ($cashTally)
                    <div class="cash-box">
                        <div class="cash-row"><span>Tunai Pembukaan</span><span class="amt">RM {{ number_format($cashTally['openingCash'], 2) }}</span></div>
                        <div class="cash-row"><span>+ Jualan Cash</span><span class="amt">RM {{ number_format($summary['cashSales'], 2) }}</span></div>
                        <div class="cash-row total"><span>Jangkaan Tunai</span><span class="amt">RM {{ number_format($cashTally['jangkaan'], 2) }}</span></div>
                        @if ($cashTally['sebenar'] !== null)
                            <div class="cash-row"><span>Tunai Sebenar</span><span class="amt">RM {{ number_format($cashTally['sebenar'], 2) }}</span></div>
                            <div class="cash-row variance {{ $cashTally['beza'] == 0 ? 'good' : 'bad' }}">
                                <span>Beza</span><span class="amt">RM {{ number_format($cashTally['beza'], 2) }}</span>
                            </div>
                        @endif
                    </div>
                @else
                    <p class="empty">Tiada sesi dibuka pada tarikh ini.</p>
                @endif
            </section>

            <section>
                <h2>Jenis Order</h2>
                <table class="report">
                    <thead><tr><th>Jenis</th><th class="num">Jumlah</th></tr></thead>
                    <tbody>
                        <tr><td>Dine In</td><td class="num">RM {{ number_format($summary['dineInSales'], 2) }}</td></tr>
                        <tr><td>Take Away</td><td class="num">RM {{ number_format($summary['takeawaySales'], 2) }}</td></tr>
                    </tbody>
                </table>
            </section>
        </div>

        <section>
            <h2>Item Terlaris</h2>
            @if ($summary['topItems']->isEmpty())
                <p class="empty">Tiada jualan hari ini.</p>
            @else
                <div class="pill-list">
                    @foreach ($summary['topItems'] as $i => $item)
                        <div class="pill-row">
                            <span class="rank">{{ $i + 1 }}</span>
                            <span class="name">{{ $item->product_name }}</span>
                            <span class="qty">{{ $item->qty_sold }} unit</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section>
            <h2>Order Dibatalkan</h2>
            @if ($summary['voidOrders']->isEmpty())
                <div class="void-note empty">Tiada order dibatalkan hari ini.</div>
            @else
                @foreach ($summary['voidOrders'] as $void)
                    <div class="void-note">{{ $void->order_number }} - RM {{ number_format($void->total, 2) }} - {{ $void->void_reason }}</div>
                @endforeach
            @endif
        </section>

        <footer class="doc">
            <span>Dijana automatik oleh sistem POS {{ $session->project->name }}</span>
            <span>Muka 1 / 1</span>
        </footer>
    </div>
</body>
</html>
