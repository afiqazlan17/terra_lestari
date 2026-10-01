<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pelarasan Jualan</h2>
            <a href="{{ route('finance.sales') }}" class="text-gray-500 hover:text-gray-700 text-sm px-2">
                &larr; Kembali ke Jualan
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if ($backfilled->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-4 py-3 bg-red-50 border-b border-red-200 text-sm text-red-800">
                        <p class="font-semibold">{{ $backfilled->count() }} hari Tutup Hari diisi semula - semak jualan</p>
                        <p class="text-xs mt-1">
                            Kalau jualan hari-hari ni <strong>dah di-key-in dalam POS</strong> (nampak "dalam POS" ada angka), jualan dah masuk sistem -
                            tekan <strong>"Jualan dah lengkap dalam POS"</strong> di bawah, jangan tambah apa-apa. Angka di bawah hanya untuk hari yang
                            jualan memang tak di-key-in langsung.
                        </p>
                        <p class="text-xs mt-1">
                            <strong>Jualan Tunai</strong> = Tunai Akhir &minus; Duit Buka &minus; jualan tunai yang dah ada dalam POS.
                            <strong>Jualan QR</strong> = QR Akhir &minus; jualan QR yang dah ada dalam POS.
                            Pastikan <strong>Duit Buka</strong> betul untuk setiap hari - tukar dan Jualan Tunai akan dikira semula.
                            Isi 0 kalau hari tu tiada jualan nak ditambah. Biar kosong untuk sahkan kemudian.
                        </p>
                    </div>
                    <form method="POST" action="{{ route('sales-adjustments.store-backfilled') }}">
                        @csrf
                        <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center gap-2 text-xs text-gray-600" x-data="{ amount: 0 }">
                            <span>Semua hari buka dengan duit laci sama?</span>
                            <span>RM</span>
                            <input type="number" step="0.01" min="0" x-model.number="amount" class="rounded-md border-gray-300 shadow-sm text-sm w-24">
                            <button type="button" @click="$dispatch('set-all-opening', amount)" class="border border-gray-300 hover:bg-gray-50 rounded-md px-3 py-1.5 font-medium">
                                Guna untuk semua hari
                            </button>
                        </div>
                        <div class="divide-y divide-gray-100">
                            @foreach ($backfilled as $day)
                                @php $s = $day['session']; @endphp
                                <div class="p-4 flex flex-col sm:flex-row sm:items-end gap-3"
                                    x-data="{
                                        closing: {{ (float) $s->closing_cash }},
                                        posCash: {{ (float) $day['cashSales'] }},
                                        opening: {{ (float) $s->opening_cash }},
                                        cash: {{ $day['suggestedCash'] }},
                                        recalc() { this.cash = Math.max(0, Math.round((this.closing - (this.opening || 0) - this.posCash) * 100) / 100); },
                                    }"
                                    @set-all-opening.window="opening = $event.detail; recalc()">
                                    <div class="flex-1 text-xs text-gray-500">
                                        <p class="text-sm font-medium text-gray-800">{{ $day['date']->translatedFormat('l, d F Y') }}</p>
                                        <p>Tunai Akhir RM {{ number_format($s->closing_cash, 2) }} &middot; tunai dalam POS RM {{ number_format($day['cashSales'], 2) }}</p>
                                        <p>QR Akhir RM {{ number_format($s->closing_qr, 2) }} &middot; QR dalam POS RM {{ number_format($day['qrSales'], 2) }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Duit Buka (RM)</label>
                                        <input type="number" step="0.01" min="0" name="entries[{{ $s->id }}][opening]"
                                            x-model.number="opening" @input="recalc()"
                                            class="rounded-md border-gray-300 shadow-sm text-sm w-28">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Jualan Tunai (RM)</label>
                                        <input type="number" step="0.01" min="0" name="entries[{{ $s->id }}][cash]"
                                            x-model.number="cash"
                                            class="rounded-md border-gray-300 shadow-sm text-sm w-28">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Jualan QR (RM)</label>
                                        <input type="number" step="0.01" min="0" name="entries[{{ $s->id }}][qr]"
                                            value="{{ number_format($day['suggestedQr'], 2, '.', '') }}"
                                            class="rounded-md border-gray-300 shadow-sm text-sm w-28">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="p-4 border-t border-gray-100 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3">
                            <button type="submit" name="mode" value="dismiss"
                                onclick="return confirm('Tutup senarai ni tanpa tambah apa-apa jualan? Pilih ni kalau semua jualan hari-hari tu memang dah di-key-in dalam POS.')"
                                class="border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-semibold px-4 py-2 rounded-lg">
                                Jualan dah lengkap dalam POS - tutup senarai
                            </button>
                            <x-primary-button type="submit">Sahkan Jualan</x-primary-button>
                        </div>
                    </form>
                </div>
            @endif

            <div class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-xs text-amber-800">
                Guna ni HANYA bila jualan sebenar sudah dikira dalam duit tunai/QR sebenar semasa Tutup Hari, tapi
                tak sempat di-key-in sistem masa tu (contoh: sistem down). Pelarasan ini akan tambah dalam Jualan
                untuk hari tersebut supaya Jangkaan padan dengan angka Sebenar yang dah direkod - bukan untuk
                tambah jualan baru yang tak pernah berlaku.
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('sales-adjustments.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="date" value="Tarikh (hari yang dah tutup)" />
                        <x-text-input id="date" name="date" type="date" class="mt-1 block w-full sm:w-64" :value="old('date')" required />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <x-input-label for="cash" value="Tunai (RM)" />
                            <x-text-input id="cash" name="cash" type="text" inputmode="decimal" data-money-input class="mt-1 block w-full" :value="old('cash')" />
                        </div>
                        <div>
                            <x-input-label for="qr" value="QR / DuitNow (RM)" />
                            <x-text-input id="qr" name="qr" type="text" inputmode="decimal" data-money-input class="mt-1 block w-full" :value="old('qr')" />
                        </div>
                        <div>
                            <x-input-label for="card" value="Kad (RM)" />
                            <x-text-input id="card" name="card" type="text" inputmode="decimal" data-money-input class="mt-1 block w-full" :value="old('card')" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="reason" value="Sebab" />
                        <x-text-input id="reason" name="reason" type="text" class="mt-1 block w-full"
                            :value="old('reason', 'Tidak sempat direkod semasa sistem down')" required />
                    </div>
                    <x-input-error :messages="$errors->all()" class="mt-1" />
                    <x-primary-button type="submit">Tambah Pelarasan</x-primary-button>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <h3 class="px-4 py-3 bg-gray-50 text-sm font-semibold text-gray-600">Sejarah Pelarasan</h3>
                @if ($history->isEmpty())
                    <p class="p-6 text-sm text-gray-400">Belum ada pelarasan direkodkan.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs text-gray-500 uppercase">
                                    <th class="px-4 py-2">Tarikh</th>
                                    <th class="px-4 py-2">Kaedah</th>
                                    <th class="px-4 py-2">Sebab</th>
                                    <th class="px-4 py-2 text-right">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($history as $order)
                                    @php $item = $order->items->first(fn ($i) => str_starts_with($i->product_name, 'Pelarasan Jualan')); @endphp
                                    <tr>
                                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $order->created_at->format('d F Y') }}</td>
                                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $order->paymentMethodLabel() }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ \Illuminate\Support\Str::between($item?->product_name ?? '', '(', ')') }}</td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-900 whitespace-nowrap">RM {{ number_format($order->total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
