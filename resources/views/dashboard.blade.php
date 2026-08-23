<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard <span class="text-gray-400 font-normal">{{ $project->name }}</span>
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
            @if (Auth::user()->hasFullAccess())
                {{-- Capital injection --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6" x-data="{ showForm: false }">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-sm font-medium text-gray-700">Modal Awal (Terra Lestari OCBC)</p>
                        <button type="button" @click="showForm = ! showForm" class="text-sm text-amber-600 hover:underline">
                            <span x-show="! showForm">+ Rekod Modal</span>
                            <span x-show="showForm" x-cloak>Batal</span>
                        </button>
                    </div>
                    <p class="text-2xl font-semibold text-gray-900 mb-4">RM {{ number_format($totalCapitalInjected, 2) }}</p>

                    <form x-show="showForm" x-cloak method="POST" action="{{ route('capital-injections.store') }}"
                        class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end mb-4 pb-4 border-b border-gray-100">
                        @csrf
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Jumlah (RM)</label>
                            <input type="number" step="0.01" min="0.01" name="amount" required class="rounded-md border-gray-300 shadow-sm text-sm w-full">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Tarikh</label>
                            <input type="date" name="injected_at" value="{{ now()->toDateString() }}" required class="rounded-md border-gray-300 shadow-sm text-sm w-full">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Akaun Sumber</label>
                            <input type="text" name="source_account" value="Terra Lestari OCBC" class="rounded-md border-gray-300 shadow-sm text-sm w-full">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Nota (opsyenal)</label>
                            <input type="text" name="notes" class="rounded-md border-gray-300 shadow-sm text-sm w-full">
                        </div>
                        <div class="sm:col-span-4">
                            <x-primary-button type="submit">Simpan</x-primary-button>
                        </div>
                    </form>

                    @if ($recentCapitalInjections->isEmpty())
                        <p class="text-sm text-gray-400">Tiada rekod modal lagi.</p>
                    @else
                        <div class="divide-y divide-gray-100">
                            @foreach ($recentCapitalInjections as $injection)
                                <div class="py-2 flex items-center justify-between text-sm">
                                    <div>
                                        <p class="text-gray-800">{{ $injection->source_account }}</p>
                                        <p class="text-gray-400">
                                            {{ $injection->injected_at->format('d M Y') }} &middot; {{ $injection->recordedBy->name }}
                                            @if ($injection->notes)
                                                &middot; {{ $injection->notes }}
                                            @endif
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <p class="font-medium text-gray-900">RM {{ number_format($injection->amount, 2) }}</p>
                                        <form method="POST" action="{{ route('capital-injections.destroy', $injection) }}" onsubmit="return confirm('Padam rekod ni?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:underline text-xs">Padam</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
