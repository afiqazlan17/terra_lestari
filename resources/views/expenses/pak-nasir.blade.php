<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pak Nasir</h2>
            <a href="{{ route('expenses.index') }}" class="text-gray-500 hover:text-gray-700 text-sm px-2">
                &larr; Kembali ke Perbelanjaan
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg p-4">
                <div class="flex items-baseline justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-800">Ringkasan {{ $summary['month']->translatedFormat('F Y') }}</h3>
                    <div class="flex items-center gap-2 text-xs">
                        <a href="{{ route('expenses.pak-nasir', ['month' => $summary['month']->copy()->subMonth()->format('Y-m')]) }}" class="text-gray-500 hover:underline">&larr; Bulan Lepas</a>
                        <a href="{{ route('expenses.pak-nasir', ['month' => $summary['month']->copy()->addMonth()->format('Y-m')]) }}" class="text-gray-500 hover:underline">Bulan Depan &rarr;</a>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3 text-sm">
                    <div class="border border-gray-100 rounded-lg p-3">
                        <p class="text-xs text-gray-500 uppercase mb-1">Jumlah Dibayar</p>
                        <p class="text-lg font-semibold text-gray-800">RM {{ number_format($summary['total'], 2) }}</p>
                    </div>
                    <div class="border border-gray-100 rounded-lg p-3">
                        <p class="text-xs text-gray-500 uppercase mb-1">Bayaran Bulanan</p>
                        <p class="text-lg font-semibold {{ $summary['monthlyPaid'] ? 'text-green-600' : 'text-amber-600' }}">
                            {{ $summary['monthlyPaid'] ? 'Sudah Dibayar' : 'Belum Dibayar' }}
                        </p>
                    </div>
                    <div class="border border-gray-100 rounded-lg p-3">
                        <p class="text-xs text-gray-500 uppercase mb-1">Hari Hadir</p>
                        <p class="text-lg font-semibold text-gray-800">{{ $summary['dailyCount'] }} hari</p>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase mb-4">Tambah Bayaran</h3>
                <form method="POST" action="{{ route('expenses.pak-nasir.store') }}" class="space-y-3">
                    @csrf
                    <div class="flex gap-2">
                        <button type="button" onclick="pnPreset({{ \App\Http\Controllers\ExpenseController::PAK_NASIR_MONTHLY }}, 'Bayaran Bulanan Pak Nasir')"
                            class="text-xs border border-gray-300 text-gray-600 hover:bg-gray-50 font-semibold px-3 py-1.5 rounded-lg">
                            RM{{ number_format(\App\Http\Controllers\ExpenseController::PAK_NASIR_MONTHLY, 0) }} (Bulanan)
                        </button>
                        <button type="button" onclick="pnPreset({{ \App\Http\Controllers\ExpenseController::PAK_NASIR_DAILY }}, 'Bayaran Harian Pak Nasir')"
                            class="text-xs border border-gray-300 text-gray-600 hover:bg-gray-50 font-semibold px-3 py-1.5 rounded-lg">
                            RM{{ number_format(\App\Http\Controllers\ExpenseController::PAK_NASIR_DAILY, 0) }} (Harian)
                        </button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <x-input-label for="pn-date" value="Tarikh" />
                            <input id="pn-date" name="purchase_date" type="date" value="{{ old('purchase_date', now()->toDateString()) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <x-input-label for="pn-amount" value="Jumlah (RM)" />
                            <input id="pn-amount" name="amount" type="text" inputmode="decimal" data-money-input value="{{ old('amount') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <x-input-label for="pn-description" value="Keterangan" />
                            <input id="pn-description" name="description" type="text" value="{{ old('description') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                    </div>
                    <x-input-error :messages="$errors->all()" class="mt-1" />
                    <x-primary-button type="submit">Rekod Bayaran</x-primary-button>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                @if ($entries->isEmpty())
                    <p class="p-8 text-center text-gray-400">Belum ada bayaran Pak Nasir direkodkan.</p>
                @else
                    <div class="overflow-x-auto" x-data="{ manageOpenId: null }">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs text-gray-500 uppercase">
                                    <th class="px-4 py-3 whitespace-nowrap">Tarikh</th>
                                    <th class="px-4 py-3">Keterangan</th>
                                    <th class="px-4 py-3 text-right whitespace-nowrap">Jumlah</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($entries as $entry)
                                    <tr class="{{ $entry->isVoided() ? 'opacity-60' : '' }}">
                                        <td class="px-4 py-3 whitespace-nowrap {{ $entry->isVoided() ? 'line-through text-gray-400' : 'text-gray-600' }}">{{ $entry->purchase_date->format('d F Y') }}</td>
                                        <td class="px-4 py-3 {{ $entry->isVoided() ? 'line-through text-gray-400' : 'text-gray-800' }}">
                                            {{ $entry->description }}
                                            @if ($entry->isVoided())
                                                <p class="text-xs text-red-500 mt-0.5 no-underline">
                                                    <span class="inline-flex rounded-full px-2 py-0.5 bg-red-100 text-red-700 font-medium">Voided</span>
                                                    {{ $entry->voidedBy?->name }} · {{ $entry->voided_at->format('d/m/Y H:i') }}: {{ $entry->void_reason }}
                                                </p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium whitespace-nowrap {{ $entry->isVoided() ? 'line-through text-gray-400' : 'text-gray-900' }}">RM {{ number_format($entry->amount, 2) }}</td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            @if (auth()->user()->hasFullAccess() && ! $entry->isVoided())
                                                <button type="button" @click="manageOpenId = manageOpenId === {{ $entry->id }} ? null : {{ $entry->id }}" class="text-amber-600 hover:underline text-xs">
                                                    Manage
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr x-show="manageOpenId === {{ $entry->id }}" x-cloak x-data="{ editing: false }">
                                        <td colspan="4" class="px-4 py-3 bg-gray-50">
                                            <div class="flex flex-wrap items-center gap-4 text-xs">
                                                <button type="button" @click="editing = ! editing" class="text-gray-600 hover:underline">
                                                    <span x-text="editing ? 'Batal Edit' : 'Edit'"></span>
                                                </button>
                                                <form method="POST" action="{{ route('expenses.void', $entry) }}" onsubmit="return submitVoidForm(this)">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="void_reason">
                                                    <button type="submit" class="text-red-500 hover:underline">Void</button>
                                                </form>
                                            </div>
                                            <div x-show="editing" x-cloak class="mt-3 pt-3 border-t border-gray-200">
                                                <form method="POST" action="{{ route('expenses.update', $entry) }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="category" value="{{ \App\Models\Purchase::CATEGORY_GAJI }}">
                                                    <input type="hidden" name="supplier_name" value="{{ \App\Http\Controllers\ExpenseController::PAK_NASIR_SUPPLIER }}">
                                                    <div>
                                                        <label class="block text-xs text-gray-500 mb-1">Tarikh</label>
                                                        <input type="date" name="purchase_date" value="{{ $entry->purchase_date->toDateString() }}" required class="rounded-md border-gray-300 shadow-sm text-sm w-full">
                                                    </div>
                                                    <div class="sm:col-span-2">
                                                        <label class="block text-xs text-gray-500 mb-1">Keterangan</label>
                                                        <input type="text" name="description" value="{{ $entry->description }}" required class="rounded-md border-gray-300 shadow-sm text-sm w-full">
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs text-gray-500 mb-1">Jumlah (RM)</label>
                                                        <input type="number" step="0.01" min="0" name="amount" value="{{ $entry->amount }}" required class="rounded-md border-gray-300 shadow-sm text-sm w-full">
                                                    </div>
                                                    <div class="flex items-end">
                                                        <x-primary-button type="submit" class="!py-1.5 !px-3 text-xs">Simpan</x-primary-button>
                                                    </div>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function pnPreset(amount, description) {
            const amountInput = document.getElementById('pn-amount');
            amountInput.value = amount.toFixed(2);
            amountInput.dispatchEvent(new Event('input', { bubbles: true }));
            document.getElementById('pn-description').value = description;
        }

        function submitVoidForm(form) {
            const reason = prompt('Sebab void bayaran ini:');
            if (!reason || !reason.trim()) return false;
            form.void_reason.value = reason.trim();
            return true;
        }
    </script>
</x-app-layout>
