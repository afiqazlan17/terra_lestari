<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Tutup Hari - {{ $session->opened_at->translatedFormat('l, d F Y') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <a href="{{ route('daily-session.reports.index') }}" class="text-sm text-amber-600 hover:underline">&larr; Kembali ke Senarai Laporan</a>

            <div class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-xs text-amber-800">
                Guna untuk betulkan salah taip sahaja. Angka asal dan siapa yang ubah akan direkod dalam nota hari ni.
                Jualan dalam sistem tak berubah.
            </div>

            <form method="POST" action="{{ route('daily-session.update', $session) }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                @csrf
                @method('PATCH')

                @foreach (['opening_cash' => 'Tunai Pembukaan (RM)', 'closing_cash' => 'Tunai Sebenar (RM)', 'closing_qr' => 'QR/DuitNow Sebenar (RM)'] as $field => $label)
                    <div>
                        <x-input-label :for="$field" :value="$label" />
                        <x-text-input :id="$field" :name="$field" type="text" inputmode="decimal" data-money-input required
                            class="mt-1 block w-full sm:w-48" :value="old($field, number_format((float) $session->$field, 2, '.', ''))" />
                        <x-input-error :messages="$errors->get($field)" class="mt-1" />
                    </div>
                @endforeach

                <x-primary-button type="submit">Simpan</x-primary-button>
            </form>

            @if ($session->notes)
                <div class="bg-white shadow-sm sm:rounded-lg p-4 text-xs text-gray-500">
                    <p class="font-semibold text-gray-600 mb-1">Nota</p>
                    <p class="whitespace-pre-line">{{ $session->notes }}</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
