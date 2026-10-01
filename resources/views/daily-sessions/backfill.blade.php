<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Isi Tutup Hari Yang Tertunggak</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <a href="{{ route('dashboard') }}" class="text-sm text-amber-600 hover:underline">&larr; Kembali ke Dashboard</a>

            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-800">
                <p>
                    Sesi dibuka {{ $staleSession->opened_at->translatedFormat('d F Y') }} tak pernah ditutup, jadi
                    {{ count($missingDates) }} hari di bawah tiada rekod Tutup Hari lagi. Isi tunai akhir dan
                    QR/DuitNow diterima untuk <strong>setiap hari</strong> ikut apa yang sebenarnya dikira/dicatat hari tu.
                </p>
                <p class="mt-1">
                    Baki pembukaan hari pertama (RM {{ number_format($staleSession->opening_cash, 2) }}) diambil dari rekod
                    Buka Hari asal. Untuk hari-hari seterusnya, baki pembukaan disambung terus dari tunai akhir hari sebelum - tak perlu isi semula.
                </p>
            </div>

            <form method="POST" action="{{ route('daily-session.backfill.store') }}" class="space-y-4">
                @csrf

                <div class="bg-white shadow-sm sm:rounded-lg divide-y divide-gray-100">
                    @foreach ($missingDates as $index => $date)
                        <div class="p-4 flex flex-col sm:flex-row sm:items-end gap-3">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-800">{{ $date->translatedFormat('l, d F Y') }}</p>
                                <p class="text-xs text-gray-500">
                                    @if ($index === 0)
                                        Baki pembukaan: RM {{ number_format($staleSession->opening_cash, 2) }} (dari Buka Hari asal)
                                    @else
                                        Baki pembukaan: sambungan dari tunai akhir {{ $missingDates[$index - 1]->translatedFormat('d/m') }}
                                    @endif
                                </p>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Tunai Akhir (RM)</label>
                                <input type="text" inputmode="decimal" data-money-input
                                    name="entries[{{ $date->toDateString() }}][closing_cash]" required
                                    class="rounded-md border-gray-300 shadow-sm text-sm w-32">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">QR/DuitNow (RM)</label>
                                <input type="text" inputmode="decimal" data-money-input
                                    name="entries[{{ $date->toDateString() }}][closing_qr]" required
                                    class="rounded-md border-gray-300 shadow-sm text-sm w-32">
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end">
                    <x-danger-button type="submit">Simpan Semua & Tutup Hari-Hari Ini</x-danger-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
