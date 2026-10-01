@php
    $rm = fn ($v) => ($v < 0 ? '−' : '').'RM '.number_format(abs($v), 2);
    $pct = fn ($v) => number_format($v, 1).'%';
    $maxDaily = max($dailyTrend->max('total'), 1);
@endphp
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Prestasi {{ $from->format('d-m-Y') }} - {{ $to->format('d-m-Y') }}</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap">
    <style>
        :root {
            --bg: #efece7; --paper: #fffdfa; --ink: #241b16; --ink-muted: #6b5d53; --ink-faint: #a3958a;
            --rule: #e3dbd0; --accent: #8a2e28; --accent-soft: #f6e9e7;
            --good: #2f6d4f; --good-soft: #e9f3ee; --bad: #a13a2c; --bad-soft: #faeae7;
        }
        * { box-sizing: border-box; }
        body { background: var(--bg); color: var(--ink); margin: 0; padding: 24px 16px 60px;
            font-family: 'Figtree', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif; }
        .toolbar { max-width: 820px; margin: 0 auto 16px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-end; gap: 10px; }
        .toolbar form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 8px; font-size: 12px; color: var(--ink-muted); }
        .toolbar label { display: flex; flex-direction: column; gap: 3px; }
        .toolbar input { font: inherit; font-size: 13px; padding: 6px 8px; border: 1px solid var(--rule); border-radius: 6px; background: var(--paper); color: var(--ink); }
        .toolbar a, .toolbar button { display: inline-block; padding: 8px 14px; border-radius: 6px; font: inherit; font-size: 13px; font-weight: 600;
            cursor: pointer; text-decoration: none; border: 1px solid var(--rule); background: var(--paper); color: var(--ink-muted); }
        .toolbar .primary { background: var(--accent); color: #fff; border-color: var(--accent); }
        .toolbar .actions { display: flex; gap: 8px; }
        .page { max-width: 820px; margin: 0 auto; background: var(--paper); border: 1px solid var(--rule); border-radius: 4px;
            padding: 44px 48px 40px; box-shadow: 0 1px 3px rgba(30, 20, 15, 0.06); }
        header.doc { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; padding-bottom: 18px; border-bottom: 2px solid var(--ink); margin-bottom: 22px; }
        header.doc .brand { font-size: 20px; font-weight: 700; letter-spacing: -0.01em; }
        header.doc .brand small { display: block; font-size: 11px; font-weight: 500; color: var(--ink-faint); letter-spacing: 0.04em; text-transform: uppercase; margin-top: 2px; }
        header.doc .doc-title { text-align: right; font-size: 11.5px; color: var(--ink-muted); line-height: 1.6; }
        header.doc .doc-title strong { display: block; font-size: 14px; color: var(--ink); font-weight: 700; }
        .stat-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 12px; }
        .stat { background: var(--accent-soft); border-radius: 6px; padding: 14px 16px; }
        .stat.plain { background: transparent; border: 1px solid var(--rule); }
        .stat .label { font-size: 10.5px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--accent); }
        .stat.plain .label { color: var(--ink-faint); }
        .stat .value { font-size: 22px; font-weight: 700; margin-top: 4px; font-variant-numeric: tabular-nums; letter-spacing: -0.01em; }
        .stat .sub { font-size: 11.5px; color: var(--ink-muted); margin-top: 2px; }
        .neg { color: var(--bad); } .pos { color: var(--good); }
        section { margin-top: 28px; }
        section h2, h3 { break-after: avoid; }
        .chart-block, .bar-rows, table.report, .stat-row, .note-list { break-inside: avoid; }
        section h2 { font-size: 12px; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; color: var(--ink-faint);
            margin: 0 0 10px; padding-bottom: 6px; border-bottom: 1px solid var(--rule); }
        h3 { font-size: 12.5px; font-weight: 600; color: var(--ink-muted); margin: 0 0 8px; }
        .split-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        table.report { width: 100%; border-collapse: collapse; font-size: 13px; }
        table.report td, table.report th { padding: 7px 0; text-align: left; vertical-align: top; }
        table.report th { font-weight: 600; color: var(--ink-muted); font-size: 11px; text-transform: uppercase; letter-spacing: 0.03em; border-bottom: 1px solid var(--rule); }
        table.report .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; padding-left: 10px; }
        table.report tbody tr:not(:last-child) td { border-bottom: 1px solid var(--rule); }
        table.report tr.subtotal td { font-weight: 700; border-top: 1px solid var(--ink); }
        table.report tr.total td { font-weight: 700; font-size: 14px; border-top: 2px solid var(--ink); padding-top: 9px; }
        table.report td.muted { color: var(--ink-faint); font-size: 12px; }
        .indent { padding-left: 14px !important; color: var(--ink-muted); }
        /* vertical daily bars - one series, baseline-anchored, 2px gaps */
        .vbars { display: flex; align-items: flex-end; gap: 2px; height: 150px; border-bottom: 1px solid var(--ink-faint); }
        .vbar { flex: 1; height: 100%; display: flex; align-items: flex-end; min-width: 0; }
        .vbar .fill { width: 100%; background: var(--accent); border-radius: 3px 3px 0 0; min-height: 0; }
        .vbar:hover .fill { background: var(--ink); }
        .vlabels { display: flex; gap: 2px; margin-top: 4px; }
        .vlabels span { flex: 1; text-align: center; font-size: 9px; color: var(--ink-faint); font-variant-numeric: tabular-nums; min-width: 0; overflow: hidden; }
        .vlabels span.weekstart { color: var(--ink-muted); font-weight: 600; }
        .chart-note { font-size: 11px; color: var(--ink-faint); margin-top: 6px; }
        .bar-rows { display: flex; flex-direction: column; gap: 5px; }
        .bar-row { display: grid; grid-template-columns: 150px 1fr 92px; align-items: center; gap: 8px; font-size: 12.5px; }
        .bar-row .lbl { color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .bar-row .lbl .rank { color: var(--accent); font-weight: 700; font-size: 11px; margin-right: 4px; }
        .bar-track { height: 10px; border-radius: 3px; background: var(--accent-soft); overflow: hidden; }
        .bar-track .fill { height: 100%; background: var(--accent); border-radius: 0 3px 3px 0; }
        .bar-row:hover .bar-track .fill { background: var(--ink); }
        .bar-row .val { text-align: right; font-variant-numeric: tabular-nums; color: var(--ink-muted); white-space: nowrap; }
        .note-list { margin: 0; padding-left: 18px; font-size: 12.5px; color: var(--ink-muted); line-height: 1.6; }
        p.empty { font-size: 13px; color: var(--ink-faint); margin: 0; }
        footer.doc { margin-top: 30px; padding-top: 14px; border-top: 1px solid var(--rule); font-size: 11px; color: var(--ink-faint); display: flex; justify-content: space-between; gap: 10px; }

        @media (max-width: 640px) {
            body { padding: 12px 0 40px; }
            .toolbar { padding: 0 16px; }
            .page { padding: 24px 16px; border-left: none; border-right: none; border-radius: 0; }
            header.doc { flex-direction: column; }
            header.doc .doc-title { text-align: left; }
            .stat-row { grid-template-columns: 1fr 1fr; }
            .stat .value { font-size: 18px; }
            .split-cols { grid-template-columns: 1fr; gap: 20px; }
            .bar-row { grid-template-columns: 110px 1fr 80px; font-size: 12px; }
            .vlabels span:not(.weekstart) { visibility: hidden; }
        }
        @media print {
            body { background: #fff; padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .toolbar { display: none; }
            .page { max-width: none; border: none; box-shadow: none; padding: 0; background: #fff; }
            @page { size: A4; margin: 12mm; }
            .vbar:hover .fill, .bar-row:hover .bar-track .fill { background: var(--accent); }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <form method="GET">
            <label>Dari <input type="date" name="from" value="{{ $from->toDateString() }}"></label>
            <label>Hingga <input type="date" name="to" value="{{ $to->toDateString() }}"></label>
            <button type="submit">Jana</button>
        </form>
        <div class="actions">
            <a href="{{ route('finance.index', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">Kembali</a>
            <button type="button" class="primary" onclick="window.print()">Print / Simpan PDF</button>
        </div>
    </div>

    <div class="page">
        <header class="doc">
            <div class="brand">{{ $project->name }}<small>Laporan Prestasi Perniagaan</small></div>
            <div class="doc-title">
                <strong>{{ $from->translatedFormat('d F Y') }} &ndash; {{ $to->translatedFormat('d F Y') }}</strong>
                {{ $calendarDays }} hari &middot; {{ $operatingDays }} hari beroperasi
            </div>
        </header>

        {{-- Headline figures --}}
        <div class="stat-row">
            <div class="stat">
                <div class="label">Jumlah Jualan</div>
                <div class="value">{{ $rm($totalSales) }}</div>
            </div>
            <div class="stat">
                <div class="label">Untung Bersih</div>
                <div class="value {{ $netProfit < 0 ? 'neg' : 'pos' }}">{{ $rm($netProfit) }}</div>
                @if ($renovation > 0)
                    <div class="sub">Tanpa renovasi: {{ $rm($netProfit + $renovation) }}</div>
                @endif
            </div>
            <div class="stat">
                <div class="label">Purata Jualan Sehari</div>
                <div class="value">{{ $rm($avgPerDay) }}</div>
                <div class="sub">ikut hari beroperasi</div>
            </div>
        </div>
        <div class="stat-row">
            <div class="stat plain">
                <div class="label">Transaksi</div>
                <div class="value">{{ number_format($orderCount) }}</div>
            </div>
            <div class="stat plain">
                <div class="label">Purata Setiap Transaksi</div>
                <div class="value">{{ $rm($avgOrder) }}</div>
            </div>
            <div class="stat plain">
                <div class="label">Item Terjual</div>
                <div class="value">{{ number_format($itemsSold) }}</div>
            </div>
        </div>

        {{-- P&L --}}
        <section>
            <h2>Untung Rugi</h2>
            <table class="report">
                <tbody>
                    <tr><td>Jualan</td><td class="num">{{ $rm($totalSales) }}</td><td class="num muted"></td></tr>
                    <tr><td class="indent">Tolak: Bahan Mentah (Belian)</td><td class="num">{{ $rm(-$foodCost) }}</td>
                        <td class="num muted">{{ $foodCostPct !== null ? $pct($foodCostPct).' jualan' : '' }}</td></tr>
                    <tr class="subtotal"><td>Untung Kasar</td><td class="num {{ $grossProfit < 0 ? 'neg' : '' }}">{{ $rm($grossProfit) }}</td><td></td></tr>
                    @foreach ($costByCategory->except(\App\Models\Purchase::CATEGORY_BAHAN_MENTAH) as $row)
                        <tr><td class="indent">Tolak: {{ $row['label'] }}</td><td class="num">{{ $rm(-$row['amount']) }}</td>
                            <td class="num muted">{{ $totalSales > 0 ? $pct($row['amount'] / $totalSales * 100).' jualan' : '' }}</td></tr>
                    @endforeach
                    <tr class="total"><td>Untung Bersih</td><td class="num {{ $netProfit < 0 ? 'neg' : 'pos' }}">{{ $rm($netProfit) }}</td><td></td></tr>
                </tbody>
            </table>
            @if ($menuMargin)
                <p class="chart-note">
                    Margin menu ikut kos resepi: <strong>{{ $rm($menuMargin['margin']) }}</strong> ({{ $pct($menuMargin['pct']) }})
                    &middot; meliputi {{ $pct($menuMargin['coverage']) }} jualan menu yang ada kos ditetapkan.
                </p>
            @endif
        </section>

        {{-- Trend --}}
        <section>
            <h2>Trend Jualan Harian</h2>
            <div class="chart-block">
            <div class="vbars" role="img" aria-label="Jualan harian {{ $from->format('d/m') }} hingga {{ $to->format('d/m') }}">
                @foreach ($dailyTrend as $day)
                    <div class="vbar" title="{{ $day['date']->translatedFormat('D, d M') }}: {{ $day['total'] > 0 ? $rm($day['total']) : 'tiada jualan' }}">
                        <div class="fill" style="height: {{ round($day['total'] / $maxDaily * 100, 1) }}%"></div>
                    </div>
                @endforeach
            </div>
            <div class="vlabels" aria-hidden="true">
                @foreach ($dailyTrend as $day)
                    <span class="{{ $day['date']->isMonday() || $loop->first ? 'weekstart' : '' }}">{{ $day['date']->format('j') }}</span>
                @endforeach
            </div>
            <p class="chart-note">Tertinggi: {{ $rm($dailyTrend->max('total')) }} &middot; tebal = hari Isnin (mula minggu) &middot; kosong = cuti / tiada jualan</p>
            </div>

            <h3 style="margin-top: 18px;">Jualan Mingguan</h3>
            <table class="report">
                <thead><tr><th>Minggu</th><th class="num">Hari buka</th><th class="num">Jualan</th><th class="num">Purata sehari</th></tr></thead>
                <tbody>
                    @foreach ($weekly as $week)
                        <tr>
                            <td>{{ $week['start']->translatedFormat('d M') }} &ndash; {{ $week['end']->translatedFormat('d M') }}</td>
                            <td class="num">{{ $week['days'] }}</td>
                            <td class="num">{{ $rm($week['total']) }}</td>
                            <td class="num">{{ $week['days'] ? $rm($week['total'] / $week['days']) : '–' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        {{-- Best sellers --}}
        <section>
            <h2>Item Paling Laku</h2>
            <div class="split-cols">
                <div>
                    <h3>Ikut kuantiti terjual</h3>
                    @php $maxQty = max($topByQty->max('qty'), 1); @endphp
                    <div class="bar-rows">
                        @forelse ($topByQty as $i => $item)
                            <div class="bar-row" title="{{ $item['name'] }}: {{ number_format($item['qty']) }} unit, {{ $rm($item['revenue']) }}">
                                <span class="lbl"><span class="rank">{{ $i + 1 }}</span>{{ $item['name'] }}</span>
                                <div class="bar-track"><div class="fill" style="width: {{ $item['qty'] / $maxQty * 100 }}%"></div></div>
                                <span class="val">{{ number_format($item['qty']) }} unit</span>
                            </div>
                        @empty
                            <p class="empty">Tiada jualan.</p>
                        @endforelse
                    </div>
                </div>
                <div>
                    <h3>Ikut nilai jualan (RM)</h3>
                    @php $maxRev = max($topByRevenue->max('revenue'), 1); @endphp
                    <div class="bar-rows">
                        @forelse ($topByRevenue as $i => $item)
                            <div class="bar-row" title="{{ $item['name'] }}: {{ $rm($item['revenue']) }}, {{ number_format($item['qty']) }} unit">
                                <span class="lbl"><span class="rank">{{ $i + 1 }}</span>{{ $item['name'] }}</span>
                                <div class="bar-track"><div class="fill" style="width: {{ $item['revenue'] / $maxRev * 100 }}%"></div></div>
                                <span class="val">{{ $rm($item['revenue']) }}</span>
                            </div>
                        @empty
                            <p class="empty">Tiada jualan.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <section>
            <div class="split-cols">
                <div>
                    <h2>Jualan Ikut Kategori</h2>
                    @php $maxCat = max($byCategory->max('revenue'), 1); $catTotal = max($byCategory->sum('revenue'), 1); @endphp
                    <div class="bar-rows">
                        @forelse ($byCategory as $cat)
                            <div class="bar-row" title="{{ $cat['name'] }}: {{ $rm($cat['revenue']) }} ({{ $pct($cat['revenue'] / $catTotal * 100) }})">
                                <span class="lbl">{{ $cat['name'] }}</span>
                                <div class="bar-track"><div class="fill" style="width: {{ $cat['revenue'] / $maxCat * 100 }}%"></div></div>
                                <span class="val">{{ $pct($cat['revenue'] / $catTotal * 100) }}</span>
                            </div>
                        @empty
                            <p class="empty">Tiada jualan.</p>
                        @endforelse
                    </div>
                </div>
                <div>
                    <h2>Pecahan Jualan</h2>
                    <table class="report">
                        <tbody>
                            @foreach ($bySource as $row)
                                <tr><td>{{ $row['label'] }}</td><td class="num">{{ $rm($row['amount']) }}</td><td class="num muted">{{ $pct($row['pct']) }}</td></tr>
                            @endforeach
                            @foreach ($byPayment as $row)
                                <tr><td>{{ $row['label'] }}</td><td class="num">{{ $rm($row['amount']) }}</td><td class="num muted">{{ $pct($row['pct']) }}</td></tr>
                            @endforeach
                            @foreach ($byOrderType as $row)
                                <tr><td>{{ $row['label'] }}</td><td class="num">{{ number_format($row['count']) }} order</td><td class="num muted">{{ $pct($row['pct']) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        {{-- When it sells --}}
        <section>
            <h2>Bila Paling Laku</h2>
            <div class="split-cols">
                <div>
                    <h3>Purata jualan ikut hari</h3>
                    @php $maxWd = max($byWeekday->max('avg'), 1); @endphp
                    <div class="bar-rows">
                        @foreach ($byWeekday as $wd)
                            <div class="bar-row" title="{{ $wd['label'] }}: purata {{ $rm($wd['avg']) }} ({{ $wd['days'] }} hari)">
                                <span class="lbl">{{ $wd['label'] }}</span>
                                <div class="bar-track"><div class="fill" style="width: {{ $wd['avg'] / $maxWd * 100 }}%"></div></div>
                                <span class="val">{{ $wd['days'] ? $rm($wd['avg']) : '–' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div>
                    <h3>Jualan ikut jam (keseluruhan tempoh)</h3>
                    @php $maxHr = max($byHour->max('total'), 1); @endphp
                    <div class="bar-rows">
                        @forelse ($byHour as $h)
                            <div class="bar-row" title="{{ sprintf('%02d:00', $h['hour']) }}: {{ $rm($h['total']) }}, {{ $h['count'] }} order">
                                <span class="lbl">{{ sprintf('%02d:00', $h['hour']) }} &ndash; {{ sprintf('%02d:59', $h['hour']) }}</span>
                                <div class="bar-track"><div class="fill" style="width: {{ $h['total'] / $maxHr * 100 }}%"></div></div>
                                <span class="val">{{ $rm($h['total']) }}</span>
                            </div>
                        @empty
                            <p class="empty">Tiada jualan.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        {{-- Costs --}}
        <section>
            <h2>Kos &amp; Perbelanjaan</h2>
            <div class="split-cols">
                <div>
                    <h3>Ikut kategori</h3>
                    <table class="report">
                        <tbody>
                            @forelse ($costByCategory->sortByDesc('amount') as $row)
                                <tr><td>{{ $row['label'] }}</td><td class="num">{{ $rm($row['amount']) }}</td>
                                    <td class="num muted">{{ $totalCost > 0 ? $pct($row['amount'] / $totalCost * 100) : '' }}</td></tr>
                            @empty
                                <tr><td class="muted">Tiada perbelanjaan.</td></tr>
                            @endforelse
                            <tr class="total"><td>Jumlah</td><td class="num">{{ $rm($totalCost) }}</td><td></td></tr>
                        </tbody>
                    </table>
                </div>
                <div>
                    <h3>Pembekal / penerima utama</h3>
                    <table class="report">
                        <tbody>
                            @forelse ($topSuppliers as $s)
                                <tr><td>{{ $s['name'] }}</td><td class="num muted">{{ $s['count'] }}x</td><td class="num">{{ $rm($s['amount']) }}</td></tr>
                            @empty
                                <tr><td class="muted">Tiada rekod.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        {{-- Capital --}}
        <section>
            <h2>Modal</h2>
            <table class="report">
                <thead><tr><th>Tarikh</th><th>Sumber</th><th class="num">Jumlah</th></tr></thead>
                <tbody>
                    @forelse ($capitalInRange as $c)
                        <tr>
                            <td>{{ $c->injected_at->translatedFormat('d F Y') }}</td>
                            <td>{{ $c->source_account }}{{ $c->notes ? ' · '.$c->notes : '' }}</td>
                            <td class="num">{{ $rm($c->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="muted">Tiada suntikan modal dalam tempoh ni.</td></tr>
                    @endforelse
                    <tr class="subtotal"><td colspan="2">Modal dalam tempoh ni</td><td class="num">{{ $rm($capitalInRange->sum('amount')) }}</td></tr>
                    <tr><td colspan="2">Modal keseluruhan (sejak mula)</td><td class="num">{{ $rm($capitalAllTime) }}</td></tr>
                </tbody>
            </table>
        </section>

        {{-- Cash control & notes --}}
        <section>
            <h2>Kawalan Tunai &amp; Nota</h2>
            <ul class="note-list">
                <li>Tutup Hari direkod untuk <strong>{{ $cashControl['closedDays'] }}</strong> daripada {{ $operatingDays }} hari beroperasi.
                    Beza kiraan berbanding jualan POS: tunai <strong class="{{ $cashControl['cashDiff'] < -0.005 ? 'neg' : '' }}">{{ $rm($cashControl['cashDiff']) }}</strong>,
                    QR <strong class="{{ $cashControl['qrDiff'] < -0.005 ? 'neg' : '' }}">{{ $rm($cashControl['qrDiff']) }}</strong>.</li>
                @if ($adjustmentSales > 0)
                    <li>Jualan termasuk <strong>{{ $rm($adjustmentSales) }}</strong> pelarasan - jualan yang dikira masa Tutup Hari tapi tak di-key-in dalam POS.</li>
                @endif
                @if ($voided && $voided->n > 0)
                    <li>{{ $voided->n }} order dibatalkan (void), bernilai {{ $rm($voided->amount) }} - tidak termasuk dalam jualan.</li>
                @endif
                @if ($calendarDays > $operatingDays)
                    <li>{{ $calendarDays - $operatingDays }} hari tiada jualan (cuti / kedai tutup).</li>
                @endif
                @if ($renovation > 0)
                    <li>Renovasi {{ $rm($renovation) }} ialah kos sekali sahaja; tanpanya untung bersih ialah {{ $rm($netProfit + $renovation) }}.</li>
                @endif
            </ul>
        </section>

        <footer class="doc">
            <span>Dijana {{ now()->translatedFormat('d F Y, H:i') }}</span>
            <span>Sajian Baginda &middot; sistem POS</span>
        </footer>
    </div>
</body>
</html>
