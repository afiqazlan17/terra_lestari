<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tally Jualan</h2>
            <a href="{{ route('finance.sales') }}" class="text-gray-500 hover:text-gray-700 text-sm px-2">&larr; Kembali ke Jualan</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            <div class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-xs text-amber-800 space-y-1">
                <p><strong>Jualan POS</strong> = apa yang di-key-in dalam POS. <strong>Closing</strong> = apa yang dikira masa Tutup Hari (Tunai: Tunai Akhir &minus; Duit Buka).</p>
                <p>Kalau closing lebih dari jualan POS, maksudnya ada jualan tak di-key-in. Tekan <strong>Tallykan</strong> - sistem tambah beza tu sebagai jualan "Adjustment" pada hari tu.
                    Kalau closing kurang (duit kurang), sistem tak boleh tolak jualan - semak dengan staff hari tu.</p>
                <p>Pastikan <strong>Duit Buka</strong> betul dulu - untuk hari yang diisi semula, sistem cuma teka (ambil Tunai Akhir hari sebelumnya).</p>
            </div>

            <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Dari</label>
                    <input type="date" name="from" value="{{ $from->toDateString() }}" class="rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Hingga</label>
                    <input type="date" name="to" value="{{ $to->toDateString() }}" class="rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <x-secondary-button type="submit">Tapis</x-secondary-button>
            </form>

            @if ($rows->contains(fn ($row) => str_contains((string) $row['session']->notes, \App\Models\DailySession::BACKFILL_NOTE)))
                <form method="POST" action="{{ route('sales-tally.backfilled-opening') }}"
                    class="bg-white shadow-sm sm:rounded-lg p-4 flex flex-wrap items-center gap-2 text-sm text-gray-600"
                    onsubmit="return confirm('Set Duit Buka ni untuk SEMUA hari yang diisi semula dalam senarai? Hari biasa tak diusik.')">
                    @csrf
                    <input type="hidden" name="from" value="{{ $from->toDateString() }}">
                    <input type="hidden" name="to" value="{{ $to->toDateString() }}">
                    <span>Duit Buka untuk semua hari <span class="text-amber-600">diisi semula</span>: RM</span>
                    <input type="number" step="0.01" min="0" name="opening_cash" value="0" required class="rounded-md border-gray-300 shadow-sm text-sm w-24">
                    <x-secondary-button type="submit">Simpan untuk semua</x-secondary-button>
                </form>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                @if ($rows->isEmpty())
                    <p class="p-6 text-sm text-gray-400">Tiada hari yang dah ditutup dalam tempoh ni.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs text-gray-500 uppercase">
                                    <th class="px-4 py-2">Tarikh</th>
                                    <th class="px-4 py-2">Duit Buka</th>
                                    <th class="px-4 py-2 text-right">Tunai POS</th>
                                    <th class="px-4 py-2 text-right">Tunai Closing</th>
                                    <th class="px-4 py-2 text-right">QR POS</th>
                                    <th class="px-4 py-2 text-right">QR Closing</th>
                                    <th class="px-4 py-2 text-right"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($rows as $row)
                                    @php $s = $row['session']; $formId = 'tally-'.$s->id; @endphp
                                    <tr x-data="{
                                            savedOpening: {{ (float) $s->opening_cash }},
                                            opening: {{ (float) $s->opening_cash }},
                                            closingCash: {{ (float) $s->closing_cash }},
                                            cashPos: {{ $row['cashPos'] }},
                                            qrClosing: {{ (float) $s->closing_qr }},
                                            qrPos: {{ $row['qrPos'] }},
                                            r(n) { return Math.round(n * 100) / 100; },
                                            get cashClosing() { return this.r(this.closingCash - (Number(this.opening) || 0)); },
                                            get cashGap() { return this.r(this.cashClosing - this.cashPos); },
                                            get qrGap() { return this.r(this.qrClosing - this.qrPos); },
                                            get tallied() { return Math.abs(this.cashGap) < 0.005 && Math.abs(this.qrGap) < 0.005; },
                                            get canAdd() { return this.cashGap > 0 || this.qrGap > 0; },
                                            get openingChanged() { return this.r(Number(this.opening) || 0) !== this.r(this.savedOpening); },
                                            rm(n) { return n.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
                                        }">
                                        <td class="px-4 py-3 whitespace-nowrap align-top">
                                            <p class="text-gray-800 font-medium">{{ $s->opened_at->translatedFormat('D, d M Y') }}</p>
                                            <p class="text-xs text-gray-400">
                                                Tunai Akhir RM {{ number_format($s->closing_cash, 2) }}
                                                @if (str_contains((string) $s->notes, \App\Models\DailySession::BACKFILL_NOTE))
                                                    &middot; <span class="text-amber-600">diisi semula</span>
                                                @endif
                                                @if ($row['hasAdjustment'])
                                                    &middot; <span class="text-indigo-600">ada adjustment</span>
                                                @endif
                                            </p>
                                        </td>
                                        <td class="px-4 py-3 align-top">
                                            <input type="number" step="0.01" min="0" name="opening_cash" form="{{ $formId }}"
                                                x-model="opening" required
                                                class="rounded-md border-gray-300 shadow-sm text-sm w-24">
                                        </td>
                                        <td class="px-4 py-3 text-right text-gray-600 whitespace-nowrap align-top">{{ number_format($row['cashPos'], 2) }}</td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap align-top">
                                            <p class="text-gray-800" x-text="rm(cashClosing)"></p>
                                            <p class="text-xs" x-show="Math.abs(cashGap) >= 0.005"
                                                :class="cashGap > 0 ? 'text-green-600' : 'text-red-600'"
                                                x-text="(cashGap > 0 ? '+' : '') + rm(cashGap)"></p>
                                        </td>
                                        <td class="px-4 py-3 text-right text-gray-600 whitespace-nowrap align-top">{{ number_format($row['qrPos'], 2) }}</td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap align-top">
                                            <p class="text-gray-800">{{ number_format($s->closing_qr, 2) }}</p>
                                            <p class="text-xs" x-show="Math.abs(qrGap) >= 0.005"
                                                :class="qrGap > 0 ? 'text-green-600' : 'text-red-600'"
                                                x-text="(qrGap > 0 ? '+' : '') + rm(qrGap)"></p>
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap align-top">
                                            <form id="{{ $formId }}" method="POST" action="{{ route('sales-tally.tally', $s) }}"
                                                @submit="if (canAdd && ! confirm('Tambah jualan Adjustment untuk {{ $s->opened_at->translatedFormat('d F Y') }}?' + (cashGap > 0 ? '\nTunai +RM ' + rm(cashGap) : '') + (qrGap > 0 ? '\nQR +RM ' + rm(qrGap) : ''))) $event.preventDefault()">
                                                @csrf
                                                <span x-show="tallied && ! openingChanged" class="text-green-600 text-xs font-semibold">&#10003; Dah tally</span>
                                                <button type="submit" x-show="canAdd"
                                                    class="bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold px-3 py-1.5 rounded-md">Tallykan</button>
                                                <button type="submit" x-show="! canAdd && openingChanged"
                                                    class="border border-gray-300 hover:bg-gray-50 text-gray-600 text-xs font-semibold px-3 py-1.5 rounded-md">Simpan Duit Buka</button>
                                                <p x-show="! tallied && ! canAdd && ! openingChanged" class="text-red-600 text-xs">Duit kurang - semak</p>
                                            </form>
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
</x-app-layout>
