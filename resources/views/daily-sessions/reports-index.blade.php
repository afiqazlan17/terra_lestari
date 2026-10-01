<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Semua Laporan Tutup Hari</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('dashboard') }}" class="text-sm text-amber-600 hover:underline">&larr; Kembali ke Dashboard</a>
                <a href="{{ route('daily-session.backfill') }}" class="border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-semibold px-4 py-2 rounded-lg">
                    + Isi Tutup Hari Terlepas
                </a>
            </div>

            @if ($staleOpenSession)
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-800">
                    <p class="font-medium">
                        &#9888; Sesi dibuka {{ $staleOpenSession->opened_at->translatedFormat('d F Y, H:i') }} masih belum ditutup
                        ({{ (int) $staleOpenSession->opened_at->diffInDays(now()) }} hari lalu).
                    </p>
                    <p class="mt-1">
                        Ini sebab hari-hari selepas {{ $staleOpenSession->opened_at->translatedFormat('d F Y') }} tiada dalam senarai di bawah -
                        sistem tak boleh buka sesi baru sehingga sesi ni ditutup. Jualan tetap selamat dan dikira ikut tarikh sebenar,
                        cuma rekod Tutup Hari untuk tempoh ni akan kosong.
                    </p>
                    <a href="{{ route('daily-session.backfill') }}" class="inline-block mt-2 font-medium underline">Isi tutup hari setiap hari tertunggak &rarr;</a>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                @if ($sessions->isEmpty())
                    <p class="p-6 text-sm text-gray-400">Tiada laporan lagi. Laporan akan keluar di sini selepas hari pertama ditutup.</p>
                @else
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs text-gray-500 uppercase">
                                <th class="px-6 py-2">Tarikh</th>
                                <th class="px-6 py-2">Dibuka</th>
                                <th class="px-6 py-2">Ditutup</th>
                                <th class="px-6 py-2 text-right">Laporan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($sessions as $session)
                                <tr>
                                    <td class="px-6 py-3 text-gray-800 font-medium whitespace-nowrap">{{ $session->opened_at->translatedFormat('l, d F Y') }}</td>
                                    <td class="px-6 py-3 text-gray-600 whitespace-nowrap">{{ $session->opened_at->format('H:i') }} &middot; {{ $session->openedBy->name }}</td>
                                    <td class="px-6 py-3 text-gray-600 whitespace-nowrap">{{ $session->closed_at->format('H:i') }} &middot; {{ $session->closedBy->name }}</td>
                                    <td class="px-6 py-3 text-right whitespace-nowrap">
                                        <a href="{{ route('daily-session.report', $session) }}" target="_blank" class="text-amber-600 hover:underline">Lihat Laporan</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{ $sessions->links() }}
        </div>
    </div>
</x-app-layout>
