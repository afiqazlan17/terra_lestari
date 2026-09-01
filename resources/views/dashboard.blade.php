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
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
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
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm font-medium text-gray-700 mb-3">Jualan ikut Kaedah Bayaran</p>
                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between"><span class="text-gray-600">Cash</span><span class="font-medium">RM {{ number_format($rangeSummary['cashSales'], 2) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-600">QR / DuitNow</span><span class="font-medium">RM {{ number_format($rangeSummary['qrSales'], 2) }}</span></div>
                        @if ($rangeSummary['cardSales'] > 0)
                            <div class="flex justify-between"><span class="text-gray-600">Kad</span><span class="font-medium">RM {{ number_format($rangeSummary['cardSales'], 2) }}</span></div>
                        @endif
                    </div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm font-medium text-gray-700 mb-3">Jualan ikut Sumber</p>
                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between"><span class="text-gray-600">Sajian Baginda (SB)</span><span class="font-medium">RM {{ number_format($rangeSummary['sbSales'], 2) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-600">Fresh From Kelantan (NBK)</span><span class="font-medium">RM {{ number_format($rangeSummary['nbkSales'], 2) }}</span></div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm font-medium text-gray-700 mb-3">Jenis Order</p>
                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between"><span class="text-gray-600">Dine In</span><span class="font-medium">RM {{ number_format($rangeSummary['dineInSales'], 2) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-600">Take Away</span><span class="font-medium">RM {{ number_format($rangeSummary['takeawaySales'], 2) }}</span></div>
                    </div>
                </div>
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
                @if ($dailyTrend->isEmpty())
                    <p class="text-sm text-gray-400">Tiada jualan lagi.</p>
                @else
                    @php $max = max($dailyTrend->max('total'), 1); @endphp
                    <div class="flex items-end gap-3 h-40">
                        @foreach ($dailyTrend as $point)
                            <div class="flex-1 flex flex-col items-center justify-end h-full">
                                <div class="text-xs text-gray-500 mb-1">RM {{ number_format($point['total'], 0) }}</div>
                                <div class="w-full bg-amber-400 rounded-t-md" style="height: {{ max(($point['total'] / $max) * 100, 3) }}%"></div>
                                <div class="text-xs text-gray-400 mt-1">{{ $point['day'] }}</div>
                            </div>
                        @endforeach
                    </div>
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
