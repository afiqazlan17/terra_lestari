<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Dashboard') }}
            </h2>
            <span class="text-sm text-gray-500">{{ now()->translatedFormat('l, d M Y') }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

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
                                &middot; Modal awal RM {{ number_format($currentSession->opening_cash, 2) }}
                            </p>
                        </div>
                        <form method="POST" action="{{ route('daily-session.close', $currentSession) }}" class="flex flex-wrap items-end gap-2">
                            @csrf
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Cash akhir (RM)</label>
                                <input type="text" inputmode="decimal" data-money-input name="closing_cash" required
                                    class="rounded-md border-gray-300 shadow-sm text-sm w-32">
                            </div>
                            <x-danger-button type="submit">Tutup Hari</x-danger-button>
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
                                <label class="block text-xs text-gray-500 mb-1">Modal awal (RM)</label>
                                <input type="text" inputmode="decimal" data-money-input name="opening_cash" required
                                    class="rounded-md border-gray-300 shadow-sm text-sm w-32">
                            </div>
                            <x-primary-button type="submit">Buka Hari</x-primary-button>
                        </form>
                    </div>
                @endif
            </div>

            {{-- Stat tiles --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Jualan Hari Ini</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">RM {{ number_format($todaySales, 2) }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Belian Hari Ini</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">RM {{ number_format($todayPurchases, 2) }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Untung Kasar Hari Ini</p>
                    <p class="mt-1 text-2xl font-semibold {{ $todayProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        RM {{ number_format($todayProfit, 2) }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Jualan Minggu Ini</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900">RM {{ number_format($weekSales, 2) }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Belian Minggu Ini</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900">RM {{ number_format($weekPurchases, 2) }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Untung Kasar Minggu Ini</p>
                    <p class="mt-1 text-xl font-semibold {{ $weekProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        RM {{ number_format($weekProfit, 2) }}
                    </p>
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

            {{-- Recent purchases --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-medium text-gray-700">Belian Terkini</p>
                    <a href="{{ route('purchases.index') }}" class="text-sm text-amber-600 hover:underline">Lihat semua</a>
                </div>
                @if ($recentPurchases->isEmpty())
                    <p class="text-sm text-gray-400">Tiada belian direkodkan lagi.</p>
                @else
                    <div class="divide-y divide-gray-100">
                        @foreach ($recentPurchases as $purchase)
                            <div class="py-2 flex items-center justify-between text-sm">
                                <div>
                                    <p class="text-gray-800">{{ $purchase->description }}</p>
                                    <p class="text-gray-400">{{ $purchase->purchase_date->format('d M Y') }} &middot; {{ $purchase->recordedBy->name }}</p>
                                </div>
                                <p class="font-medium text-gray-900">RM {{ number_format($purchase->amount, 2) }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
