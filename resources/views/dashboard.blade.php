<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Dashboard') }}
            </h2>
            <span class="text-sm text-gray-500">{{ now()->translatedFormat('l, d F Y') }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('closed_session_id'))
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 flex items-center justify-between gap-3">
                    <p class="text-sm text-green-800">Hari ini telah ditutup. Laporan tutup hari sedia untuk dimuat turun.</p>
                    <a href="{{ route('daily-session.report', session('closed_session_id')) }}" target="_blank"
                        class="shrink-0 bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded-lg">
                        Muat Turun Laporan
                    </a>
                </div>
            @endif

            {{-- Daily session panel --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @if ($currentSession)
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <span class="inline-flex items-center gap-1 rounded-full bg-green-100 text-green-800 px-3 py-1 text-xs font-medium">
                                &#9679; Hari ini dibuka
                            </span>
                            <p class="mt-2 text-sm text-gray-600">
                                Dibuka jam {{ $currentSession->opened_at->format('H:i') }} oleh {{ $currentSession->openedBy->name }}
                                &middot; Tunai pembukaan RM {{ number_format($currentSession->opening_cash, 2) }}
                            </p>
                        </div>
                        <form method="POST" action="{{ route('daily-session.close', $currentSession) }}" class="flex flex-col items-start gap-2" onsubmit="return sbConfirmCloseWithPendingCheck()">
                            @csrf
                            <p class="text-xs text-amber-700">Sila kira tunai di tangan sekarang dah input di ruang ini</p>
                            <div class="flex flex-wrap items-end gap-2">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Tunai Akhir (RM)</label>
                                    <input type="text" inputmode="decimal" data-money-input name="closing_cash" required
                                        class="rounded-md border-gray-300 shadow-sm text-sm w-32">
                                </div>
                                <x-danger-button type="submit">Tutup Hari</x-danger-button>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 text-gray-600 px-3 py-1 text-xs font-medium">
                                &#9679; Belum dibuka
                            </span>
                            <p class="mt-2 text-sm text-gray-600">Buka hari dahulu sebelum staff boleh mula berjualan di POS.</p>
                        </div>
                        <form method="POST" action="{{ route('daily-session.open') }}" class="flex flex-wrap items-end gap-2">
                            @csrf
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Tunai Pembukaan (RM)</label>
                                <input type="text" inputmode="decimal" data-money-input name="opening_cash" required
                                    class="rounded-md border-gray-300 shadow-sm text-sm w-32">
                            </div>
                            <x-primary-button type="submit">Buka Hari</x-primary-button>
                        </form>
                    </div>
                @endif
            </div>

            {{-- Date range picker --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-4">
                <form method="GET" class="flex flex-wrap items-end gap-2">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Dari</label>
                        <input type="date" name="from" value="{{ $from->toDateString() }}" class="rounded-md border-gray-300 shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Hingga</label>
                        <input type="date" name="to" value="{{ $to->toDateString() }}" class="rounded-md border-gray-300 shadow-sm text-sm">
                    </div>
                    <x-secondary-button type="submit">Papar</x-secondary-button>
                    <a href="{{ route('dashboard') }}" class="text-sm text-amber-600 hover:underline mb-2">Hari Ini</a>
                </form>
            </div>

            {{-- Stat tiles (range-scoped) --}}
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Jumlah Jualan</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">RM {{ number_format($rangeSummary['totalSales'], 2) }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Transaksi</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $rangeSummary['orderCount'] }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Item Terjual</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $rangeSummary['itemCount'] }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Anggaran Margin</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">
                        {{ $rangeSummary['estimatedMargin'] !== null ? 'RM '.number_format($rangeSummary['estimatedMargin'], 2) : '-' }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Anggaran sahaja (Kuih guna kos purata ikut tier harga)</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm font-medium text-gray-700 mb-3">Jualan ikut Kaedah Bayaran</p>
                    <x-donut-chart :slices="collect([
                        ['label' => 'Cash', 'value' => (float) $rangeSummary['cashSales']],
                        ['label' => 'QR / DuitNow', 'value' => (float) $rangeSummary['qrSales']],
                        $rangeSummary['cardSales'] > 0 ? ['label' => 'Kad', 'value' => (float) $rangeSummary['cardSales']] : null,
                    ])->filter()->values()->all()" />
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm font-medium text-gray-700 mb-3">Jualan ikut Sumber</p>
                    <x-donut-chart :slices="[
                        ['label' => 'Sajian Baginda (SB)', 'value' => (float) $rangeSummary['sbSales']],
                        ['label' => 'Fresh From Kelantan (NBK)', 'value' => (float) $rangeSummary['nbkSales']],
                    ]" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4">
                @if ($cashTally)
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <p class="text-sm font-medium text-gray-700 mb-3">Tunai</p>
                        <div class="space-y-1 text-sm">
                            <div class="flex justify-between"><span class="text-gray-600">Tunai Pembukaan</span><span class="font-medium">RM {{ number_format($cashTally['openingCash'], 2) }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-600">+ Jualan Cash</span><span class="font-medium">RM {{ number_format($rangeSummary['cashSales'], 2) }}</span></div>
                            <div class="flex justify-between font-semibold border-t border-gray-100 pt-1"><span>Jangkaan Tunai</span><span>RM {{ number_format($cashTally['jangkaan'], 2) }}</span></div>
                            @if ($cashTally['sebenar'] !== null)
                                <div class="flex justify-between"><span class="text-gray-600">Tunai Sebenar</span><span class="font-medium">RM {{ number_format($cashTally['sebenar'], 2) }}</span></div>
                                <div class="flex justify-between font-semibold {{ $cashTally['beza'] == 0 ? 'text-green-600' : 'text-red-600' }}">
                                    <span>Beza</span><span>RM {{ number_format($cashTally['beza'], 2) }}</span>
                                </div>
                            @else
                                <p class="text-xs text-gray-400 pt-1">Tunai Sebenar &amp; Beza akan keluar lepas Tutup Hari.</p>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <p class="text-sm font-medium text-gray-700 mb-2">Tunai</p>
                        <p class="text-xs text-gray-400">
                            @if (! $isSingleDay)
                                Tally tunai hanya untuk 1 hari — pilih tarikh Dari dan Hingga yang sama untuk lihat.
                            @else
                                Tiada sesi dibuka pada tarikh ini.
                            @endif
                        </p>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm font-medium text-gray-700 mb-3">Item Terlaris</p>
                    @if ($rangeSummary['topItems']->isEmpty())
                        <p class="text-sm text-gray-400">Tiada jualan lagi.</p>
                    @else
                        <div class="space-y-1.5">
                            @foreach ($rangeSummary['topItems'] as $i => $item)
                                <div class="flex items-center justify-between text-sm bg-amber-50 rounded-md px-3 py-1.5">
                                    <span class="text-gray-800">{{ $item->product_name }}</span>
                                    <span class="text-gray-500">{{ $item->qty_sold }} unit</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm font-medium text-gray-700 mb-3">Laporan Tutup Hari</p>
                    @if ($sessionForReport && $sessionForReport->status === 'closed')
                        <a href="{{ route('daily-session.report', $sessionForReport) }}" target="_blank"
                            class="inline-block bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium px-4 py-2 rounded-lg">
                            Lihat Laporan {{ $from->translatedFormat('d F Y') }}
                        </a>
                    @elseif ($sessionForReport)
                        <p class="text-sm text-gray-400">Laporan akan sedia lepas Tutup Hari.</p>
                    @else
                        <p class="text-sm text-gray-400">Tiada sesi pada tarikh ini.</p>
                    @endif
                    <div class="mt-3">
                        <a href="{{ route('daily-session.reports.index') }}" class="text-sm text-amber-600 hover:underline">
                            Click Here to View All Reports
                        </a>
                    </div>
                </div>
            </div>

            {{-- Trend chart --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-sm font-medium text-gray-700 mb-4">Trend Jualan 7 Hari</p>
                @if ($dailyTrend->sum('total') == 0)
                    <p class="text-sm text-gray-400">Tiada jualan lagi.</p>
                @else
                    @php
                        $padX = 30; $padTop = 26; $padBottom = 26;
                        $width = 700; $height = 200;
                        $plotWidth = $width - 2 * $padX;
                        $baselineY = $height - $padBottom;
                        $max = max($dailyTrend->max('total') * 1.15, 1);
                        $n = $dailyTrend->count();
                        $step = $n > 1 ? $plotWidth / ($n - 1) : 0;

                        $points = $dailyTrend->values()->map(function ($point, $i) use ($padX, $step, $baselineY, $padTop, $max) {
                            $y = $baselineY - ($point['total'] / $max) * ($baselineY - $padTop);

                            return ['x' => $padX + $i * $step, 'y' => $y, 'total' => $point['total'], 'day' => $point['day']];
                        });

                        $linePoints = $points->map(fn ($p) => "{$p['x']},{$p['y']}")->implode(' ');
                        $areaPath = 'M '.$points->first()['x'].','.$baselineY
                            .' L '.$points->map(fn ($p) => "{$p['x']},{$p['y']}")->implode(' L ')
                            .' L '.$points->last()['x'].','.$baselineY.' Z';
                    @endphp
                    <svg viewBox="0 0 {{ $width }} {{ $height }}" class="w-full" style="max-height: 220px;">
                        <defs>
                            <linearGradient id="trendFill" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#f59e0b" stop-opacity="0.28" />
                                <stop offset="100%" stop-color="#f59e0b" stop-opacity="0" />
                            </linearGradient>
                        </defs>
                        <line x1="{{ $padX }}" y1="{{ $baselineY }}" x2="{{ $width - $padX }}" y2="{{ $baselineY }}" stroke="#e5e7eb" stroke-width="1" />
                        <path d="{{ $areaPath }}" fill="url(#trendFill)" />
                        <polyline points="{{ $linePoints }}" fill="none" stroke="#f59e0b" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" />
                        @foreach ($points as $p)
                            <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="4" fill="#fff" stroke="#f59e0b" stroke-width="2.5" />
                            <text x="{{ $p['x'] }}" y="{{ max($p['y'] - 10, 12) }}" text-anchor="middle" class="fill-gray-500" style="font-size: 11px;">RM {{ number_format($p['total'], 0) }}</text>
                            <text x="{{ $p['x'] }}" y="{{ $height - 6 }}" text-anchor="middle" class="fill-gray-400" style="font-size: 11px;">{{ $p['day'] }}</text>
                        @endforeach
                    </svg>
                @endif
            </div>

        </div>
    </div>

    <script>
        function sbConfirmCloseWithPendingCheck() {
            try {
                const pending = JSON.parse(localStorage.getItem('sb_pending_orders') || '[]');
                if (pending.length > 0) {
                    return confirm(
                        'Amaran: ada ' + pending.length + ' order belum disync pada peranti ini. ' +
                        'Jualan tersebut TIDAK akan dikira dalam tutup hari sekarang.\n\n' +
                        'Disarankan pastikan online dan tunggu sync selesai dahulu.\n\nTeruskan tutup hari juga?'
                    );
                }
            } catch (e) {}
            return true;
        }
    </script>
</x-app-layout>
