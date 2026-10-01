<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Isi Tutup Hari Yang Terlepas</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <a href="{{ route('daily-session.reports.index') }}" class="text-sm text-amber-600 hover:underline">&larr; Kembali ke Senarai Laporan</a>

            @if ($rows->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm text-gray-500">
                    Tiada tarikh terlepas dalam 60 hari lepas - semua hari dah ada rekod Tutup Hari.
                </div>
            @else
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-800 space-y-1">
                    <p>Tarikh di bawah tiada rekod Tutup Hari. Isi <strong>Tunai Akhir</strong> dan <strong>QR/DuitNow</strong> untuk hari yang kedai buka - hari yang kedai tutup, biar kosong je.</p>
                    <p>Tunai Pembukaan boleh biar kosong - sistem akan ambil Tunai Akhir hari sebelumnya secara automatik.</p>
                </div>

                @if ($errors->any())
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-800">
                        @foreach ($errors->unique() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('daily-session.backfill.store') }}" class="space-y-4">
                    @csrf

                    <div class="bg-white shadow-sm sm:rounded-lg divide-y divide-gray-100">
                        @foreach ($rows as $row)
                            @php $key = $row['date']->toDateString(); @endphp
                            <div class="p-4 flex flex-col sm:flex-row sm:items-end gap-3">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-800">{{ $row['date']->translatedFormat('l, d F Y') }}</p>
                                    <p class="text-xs text-gray-500">
                                        Jualan dalam sistem: Tunai RM {{ number_format($row['cashSales'], 2) }} &middot; QR RM {{ number_format($row['qrSales'], 2) }}
                                    </p>
                                    @if ($row['openSession'])
                                        <p class="text-xs text-red-600">Buka Hari dibuat, tapi tak pernah ditutup</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Tunai Pembukaan (RM)</label>
                                    @if ($row['openSession'])
                                        <p class="text-sm text-gray-700 w-32 py-2">{{ number_format($row['openSession']->opening_cash, 2) }}</p>
                                    @else
                                        <input type="text" inputmode="decimal" data-money-input placeholder="Auto"
                                            name="entries[{{ $key }}][opening_cash]" value="{{ old("entries.$key.opening_cash") }}"
                                            class="rounded-md border-gray-300 shadow-sm text-sm w-32">
                                    @endif
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Tunai Akhir (RM)</label>
                                    <input type="text" inputmode="decimal" data-money-input
                                        name="entries[{{ $key }}][closing_cash]" value="{{ old("entries.$key.closing_cash") }}"
                                        class="rounded-md border-gray-300 shadow-sm text-sm w-32">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">QR/DuitNow (RM)</label>
                                    <input type="text" inputmode="decimal" data-money-input
                                        name="entries[{{ $key }}][closing_qr]" value="{{ old("entries.$key.closing_qr") }}"
                                        class="rounded-md border-gray-300 shadow-sm text-sm w-32">
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button type="submit">Simpan</x-primary-button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
