<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Semua Laporan Tutup Hari</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <a href="{{ route('dashboard') }}" class="text-sm text-amber-600 hover:underline">&larr; Kembali ke Dashboard</a>

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
