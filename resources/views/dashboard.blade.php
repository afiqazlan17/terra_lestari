<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Dashboard') }}
            </h2>
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-500">{{ now()->translatedFormat('l, d M Y') }}</span>
                <a href="https://wa.me/?text={{ urlencode($whatsappSummary) }}" target="_blank"
                    class="inline-flex items-center gap-1.5 bg-green-500 hover:bg-green-600 text-white text-sm font-medium px-3 py-1.5 rounded-lg">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91C22 6.45 17.5 2 12.04 2Zm0 18.14h-.01a8.2 8.2 0 0 1-4.19-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.22 8.22 0 0 1-1.26-4.37c0-4.54 3.7-8.24 8.25-8.24 2.2 0 4.27.86 5.83 2.42a8.19 8.19 0 0 1 2.41 5.83c0 4.55-3.7 8.24-8.24 8.24Zm4.52-6.17c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.13-.16.24-.64.81-.78.97-.15.16-.29.18-.53.06-.25-.12-1.05-.39-2-1.23-.74-.66-1.24-1.47-1.39-1.72-.14-.24-.02-.38.11-.5.11-.11.25-.29.37-.43.12-.14.16-.24.24-.4.08-.16.04-.31-.02-.43-.06-.13-.56-1.34-.76-1.84-.2-.48-.41-.42-.56-.42h-.48c-.16 0-.42.06-.65.31-.22.24-.85.83-.85 2.03s.87 2.36 1 2.52c.12.16 1.71 2.6 4.14 3.65.58.25 1.03.4 1.38.51.58.18 1.11.16 1.53.09.47-.07 1.47-.6 1.67-1.18.21-.58.21-1.07.15-1.18-.06-.1-.23-.16-.48-.28Z"/></svg>
                    WhatsApp
                </a>
            </div>
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
                                &#9679; Hari ni dibuka
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
                                <input type="number" step="0.01" min="0" name="closing_cash" required
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
                            <p class="mt-2 text-sm text-gray-600">Buka hari dulu sebelum staff boleh mula jual kat POS.</p>
                        </div>
                        <form method="POST" action="{{ route('daily-session.open') }}" class="flex flex-wrap items-end gap-2">
                            @csrf
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Modal awal (RM)</label>
                                <input type="number" step="0.01" min="0" name="opening_cash" required
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
                    <p class="text-sm text-gray-500">Jualan Hari Ni</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">RM {{ number_format($todaySales, 2) }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Belian Hari Ni</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">RM {{ number_format($todayPurchases, 2) }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Untung Kasar Hari Ni</p>
                    <p class="mt-1 text-2xl font-semibold {{ $todayProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        RM {{ number_format($todayProfit, 2) }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Jualan Minggu Ni</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900">RM {{ number_format($weekSales, 2) }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Belian Minggu Ni</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900">RM {{ number_format($weekPurchases, 2) }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Untung Kasar Minggu Ni</p>
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
