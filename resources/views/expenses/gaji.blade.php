<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gaji</h2>
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
                        <a href="{{ route('expenses.gaji', ['month' => $summary['month']->copy()->subMonth()->format('Y-m')]) }}" class="text-gray-500 hover:underline">&larr; Bulan Lepas</a>
                        <a href="{{ route('expenses.gaji', ['month' => $summary['month']->copy()->addMonth()->format('Y-m')]) }}" class="text-gray-500 hover:underline">Bulan Depan &rarr;</a>
                    </div>
                </div>
                <div class="border border-gray-100 rounded-lg p-3 text-sm mb-3">
                    <p class="text-xs text-gray-500 uppercase mb-1">Jumlah Gaji Dibayar</p>
                    <p class="text-lg font-semibold text-gray-800">RM {{ number_format($summary['total'], 2) }}</p>
                </div>
                @if ($summary['byStaff']->isNotEmpty())
                    <div class="space-y-1.5">
                        @foreach ($summary['byStaff'] as $staff => $amount)
                            <div class="flex items-center justify-between text-sm bg-gray-50 rounded-md px-3 py-1.5">
                                <span class="text-gray-700">{{ $staff }}</span>
                                <span class="text-gray-500">RM {{ number_format($amount, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase mb-4">Tambah Bayaran</h3>
                <form method="POST" action="{{ route('expenses.gaji.store') }}" class="space-y-3">
                    @csrf
                    <div class="flex gap-2">
                        <button type="button" onclick="gajiPreset({{ \App\Http\Controllers\ExpenseController::PAK_NASIR_MONTHLY }}, 'Bayaran Bulanan Pak Nasir', '{{ \App\Http\Controllers\ExpenseController::PAK_NASIR_SUPPLIER }}')"
                            class="text-xs border border-gray-300 text-gray-600 hover:bg-gray-50 font-semibold px-3 py-1.5 rounded-lg">
                            Pak Nasir - RM{{ number_format(\App\Http\Controllers\ExpenseController::PAK_NASIR_MONTHLY, 0) }} (Bulanan)
                        </button>
                        <button type="button" onclick="gajiPreset({{ \App\Http\Controllers\ExpenseController::PAK_NASIR_DAILY }}, 'Bayaran Harian Pak Nasir', '{{ \App\Http\Controllers\ExpenseController::PAK_NASIR_SUPPLIER }}')"
                            class="text-xs border border-gray-300 text-gray-600 hover:bg-gray-50 font-semibold px-3 py-1.5 rounded-lg">
                            Pak Nasir - RM{{ number_format(\App\Http\Controllers\ExpenseController::PAK_NASIR_DAILY, 0) }} (Harian)
                        </button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div>
                            <x-input-label for="gaji-date" value="Tarikh" />
                            <input id="gaji-date" name="purchase_date" type="date" value="{{ old('purchase_date', now()->toDateString()) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <x-input-label for="gaji-staff" value="Nama Staff" />
                            <x-supplier-input id="gaji-staff" name="supplier_name" class="mt-1"
                                :value="old('supplier_name')" :suppliers="$supplierNames" />
                        </div>
                        <div>
                            <x-input-label for="gaji-amount" value="Jumlah (RM)" />
                            <input id="gaji-amount" name="amount" type="text" inputmode="decimal" data-money-input value="{{ old('amount') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <x-input-label for="gaji-description" value="Keterangan" />
                            <input id="gaji-description" name="description" type="text" value="{{ old('description') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                    </div>
                    <x-input-error :messages="$errors->all()" class="mt-1" />
                    <x-primary-button type="submit">Rekod Bayaran</x-primary-button>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                @if ($entries->isEmpty())
                    <p class="p-8 text-center text-gray-400">Belum ada bayaran gaji direkodkan.</p>
                @else
                    <div class="overflow-x-auto" x-data="{ manageOpenId: null }">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs text-gray-500 uppercase">
                                    <th class="px-4 py-3 whitespace-nowrap">Tarikh</th>
                                    <th class="px-4 py-3 whitespace-nowrap">Staff</th>
                                    <th class="px-4 py-3">Keterangan</th>
                                    <th class="px-4 py-3 text-right whitespace-nowrap">Jumlah</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($entries as $entry)
                                    <tr class="{{ $entry->isVoided() ? 'opacity-60' : '' }}">
                                        <td class="px-4 py-3 whitespace-nowrap {{ $entry->isVoided() ? 'line-through text-gray-400' : 'text-gray-600' }}">{{ $entry->purchase_date->format('d F Y') }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap {{ $entry->isVoided() ? 'line-through text-gray-400' : 'text-gray-600' }}">{{ $entry->supplier_name ?? '-' }}</td>
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
                                        <td colspan="5" class="px-4 py-3 bg-gray-50">
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
                                                <form method="POST" action="{{ route('expenses.update', $entry) }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="category" value="{{ \App\Models\Purchase::CATEGORY_GAJI }}">
                                                    <div>
                                                        <label class="block text-xs text-gray-500 mb-1">Tarikh</label>
                                                        <input type="date" name="purchase_date" value="{{ $entry->purchase_date->toDateString() }}" required class="rounded-md border-gray-300 shadow-sm text-sm w-full">
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs text-gray-500 mb-1">Nama Staff</label>
                                                        <x-supplier-input id="gaji-staff-{{ $entry->id }}" name="supplier_name"
                                                            :value="$entry->supplier_name" :suppliers="$supplierNames" class="text-sm" />
                                                    </div>
                                                    <div>
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
        function gajiPreset(amount, description, staffName) {
            const amountInput = document.getElementById('gaji-amount');
            amountInput.value = amount.toFixed(2);
            amountInput.dispatchEvent(new Event('input', { bubbles: true }));
            document.getElementById('gaji-description').value = description;
            const staffInput = document.getElementById('gaji-staff');
            staffInput.value = staffName;
            staffInput.dispatchEvent(new Event('input', { bubbles: true }));
        }

        function submitVoidForm(form) {
            const reason = prompt('Sebab void bayaran ini:');
            if (!reason || !reason.trim()) return false;
            form.void_reason.value = reason.trim();
            return true;
        }
    </script>
</x-app-layout>
