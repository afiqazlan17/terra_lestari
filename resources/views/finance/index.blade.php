<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Akaun') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @include('finance.partials.subnav')
            @include('finance.partials.filter')

            <p class="text-sm text-gray-500 mb-4">
                Untung rugi bagi {{ $from->format('d M Y') }} hingga {{ $to->format('d M Y') }}
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Jualan (Revenue)</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">RM {{ number_format($revenue, 2) }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Untung Kasar</p>
                    <p class="mt-1 text-2xl font-semibold {{ $grossProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        RM {{ number_format($grossProfit, 2) }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Jualan tolak kos bahan mentah</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Untung Bersih</p>
                    <p class="mt-1 text-2xl font-semibold {{ $netProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        RM {{ number_format($netProfit, 2) }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Jualan tolak semua perbelanjaan</p>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="flex items-center justify-between mb-1">
                    <p class="text-sm font-medium text-gray-700">Food Cost</p>
                    <span class="text-xs font-medium px-2 py-1 rounded-full {{ $foodCostPercent <= 35 ? 'bg-green-100 text-green-800' : ($foodCostPercent <= 40 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                        {{ number_format($foodCostPercent, 1) }}% daripada jualan
                    </span>
                </div>
                <p class="text-xl font-semibold text-gray-900">RM {{ number_format($foodCost, 2) }}</p>
                <p class="text-xs text-gray-400 mt-2">
                    Nisbah sihat untuk kedai makan biasanya antara 28% hingga 35%. Lagi rendah, lagi baik margin.
                </p>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden mb-6">
                <div class="px-6 py-3 bg-gray-50 text-sm font-semibold text-gray-600">Perbelanjaan Ikut Kategori</div>
                @if ($purchasesByCategory->isEmpty())
                    <p class="p-6 text-sm text-gray-400">Tiada perbelanjaan direkodkan dalam tempoh ini.</p>
                @else
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <tbody class="divide-y divide-gray-100">
                            @foreach (\App\Models\Purchase::CATEGORIES as $key => $label)
                                @if (isset($purchasesByCategory[$key]))
                                    <tr>
                                        <td class="px-6 py-3 text-gray-700">{{ $label }}</td>
                                        <td class="px-6 py-3 text-right font-medium text-gray-900">RM {{ number_format($purchasesByCategory[$key], 2) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                            <tr class="bg-gray-50">
                                <td class="px-6 py-3 font-semibold text-gray-800">Jumlah Perbelanjaan</td>
                                <td class="px-6 py-3 text-right font-semibold text-gray-900">RM {{ number_format($totalExpenses, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                @endif
            </div>

            @if (Auth::user()->hasFullAccess())
                <div class="bg-white shadow-sm sm:rounded-lg p-4" x-data="{ open: false }">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <span class="text-sm text-gray-500">Modal Awal (Terra Lestari OCBC)</span>
                            <span class="ms-2 text-sm font-semibold text-gray-900">RM {{ number_format($allTimeCapitalInjected, 2) }}</span>
                            <span class="ms-1 text-xs text-gray-400">jumlah keseluruhan</span>
                        </div>
                        <button type="button" @click="open = ! open" class="text-sm text-amber-600 hover:underline whitespace-nowrap">
                            <span x-show="! open">+ Rekod</span>
                            <span x-show="open" x-cloak>Tutup</span>
                        </button>
                    </div>

                    <div x-show="open" x-cloak class="mt-4 pt-4 border-t border-gray-100">
                        <form method="POST" action="{{ route('capital-injections.store') }}" enctype="multipart/form-data"
                            class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end mb-4">
                            @csrf
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Jumlah (RM)</label>
                                <input type="text" inputmode="decimal" data-money-input name="amount" required class="rounded-md border-gray-300 shadow-sm text-sm w-full">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Tarikh</label>
                                <input type="date" name="injected_at" value="{{ now()->toDateString() }}" required class="rounded-md border-gray-300 shadow-sm text-sm w-full">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Nota (Jika ada)</label>
                                <input type="text" name="notes" class="rounded-md border-gray-300 shadow-sm text-sm w-full">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs text-gray-500 mb-1">Lampiran Statement/Resit (Jika ada)</label>
                                <input type="file" name="receipt" accept="image/*,.pdf,application/pdf" class="block w-full text-sm text-gray-600">
                            </div>
                            <div class="sm:col-span-3">
                                <x-primary-button type="submit">Simpan</x-primary-button>
                            </div>
                        </form>

                        @if ($recentCapitalInjections->isNotEmpty())
                            <div class="divide-y divide-gray-100 text-sm">
                                @foreach ($recentCapitalInjections as $injection)
                                    <div class="py-3" x-data="{ editReceipt: false }">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-gray-700">
                                                    {{ $injection->injected_at->format('d M Y') }} &middot; {{ $injection->source_account }}
                                                    @if ($injection->receipt_path)
                                                        &middot; <a href="{{ Storage::url($injection->receipt_path) }}" target="_blank" class="text-amber-600 hover:underline">Lihat lampiran</a>
                                                    @endif
                                                </p>
                                                @if ($injection->notes)
                                                    <p class="text-xs text-gray-400">{{ $injection->notes }}</p>
                                                @endif
                                                <p class="text-xs text-gray-400 mt-0.5">
                                                    Ditambah oleh {{ $injection->recordedBy->name }}, {{ $injection->created_at->format('d M Y, H:i') }}
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <p class="font-medium text-gray-900">RM {{ number_format($injection->amount, 2) }}</p>
                                                <form method="POST" action="{{ route('capital-injections.destroy', $injection) }}" onsubmit="return confirm('Padam rekod ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-500 hover:underline text-xs">Padam</button>
                                                </form>
                                            </div>
                                        </div>

                                        <div class="mt-2 flex justify-center">
                                            <button type="button" @click="editReceipt = ! editReceipt"
                                                class="text-xs text-gray-600 border border-gray-300 rounded-full px-3 py-1 hover:bg-gray-50">
                                                {{ $injection->receipt_path ? 'Tukar/Padam Lampiran' : '+ Tambah Lampiran' }}
                                            </button>
                                        </div>

                                        <div x-show="editReceipt" x-cloak class="mt-2 flex flex-wrap items-center justify-center gap-2">
                                            <form method="POST" action="{{ route('capital-injections.receipt.update', $injection) }}" enctype="multipart/form-data" class="flex items-center gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <input type="file" name="receipt" accept="image/*,.pdf,application/pdf" class="text-xs text-gray-600">
                                                <button type="submit" class="text-xs text-amber-600 hover:underline">Muat naik</button>
                                            </form>
                                            @if ($injection->receipt_path)
                                                <form method="POST" action="{{ route('capital-injections.receipt.update', $injection) }}" onsubmit="return confirm('Padam lampiran ini?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="remove_receipt" value="1">
                                                    <button type="submit" class="text-xs text-red-500 hover:underline">Padam lampiran</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
