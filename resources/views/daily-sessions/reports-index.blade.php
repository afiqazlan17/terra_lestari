<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Semua Laporan Tutup Hari</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">
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
                    <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs text-gray-500 uppercase">
                                <th class="px-4 py-2">Tarikh</th>
                                <th class="px-4 py-2 text-right">Buka</th>
                                <th class="px-4 py-2 text-right">Tunai Akhir</th>
                                <th class="px-4 py-2 text-right">Tunai POS</th>
                                <th class="px-4 py-2 text-right">Beza Tunai</th>
                                <th class="px-4 py-2 text-right">QR Akhir</th>
                                <th class="px-4 py-2 text-right">QR POS</th>
                                <th class="px-4 py-2 text-right">Beza QR</th>
                                <th class="px-4 py-2 text-right">Laporan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($sessions as $session)
                                @php
                                    $day = $session->opened_at->toDateString();
                                    $cashPos = $posSales[$day.'|'.\App\Models\Order::PAYMENT_METHOD_CASH] ?? 0;
                                    $qrPos = $posSales[$day.'|'.\App\Models\Order::PAYMENT_METHOD_QR] ?? 0;
                                    $bezaCash = (float) $session->closing_cash - (float) $session->opening_cash - $cashPos;
                                    $bezaQr = (float) $session->closing_qr - $qrPos;
                                    $isBackfilled = str_contains((string) $session->notes, \App\Models\DailySession::BACKFILL_NOTE);
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <p class="text-gray-800 font-medium">{{ $session->opened_at->translatedFormat('D, d M Y') }}</p>
                                        <p class="text-xs text-gray-400">
                                            {{ $session->closedBy->name }}
                                            @unless ($session->closed_at->isSameDay($session->opened_at) || ($session->closed_at->isSameDay($session->opened_at->copy()->addDay()) && $session->closed_at->hour < 6))
                                                &middot; <span class="text-red-600 font-medium">ditutup {{ $session->closed_at->translatedFormat('d M, H:i') }}</span>
                                            @endunless
                                            @if ($isBackfilled)
                                                &middot; <span class="text-amber-600">diisi semula</span>
                                            @endif
                                        </p>
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-600 whitespace-nowrap">{{ number_format($session->opening_cash, 2) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-800 whitespace-nowrap">{{ number_format($session->closing_cash, 2) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600 whitespace-nowrap">{{ number_format($cashPos, 2) }}</td>
                                    <td class="px-4 py-3 text-right font-medium whitespace-nowrap {{ abs($bezaCash) < 0.005 ? 'text-gray-400' : ($bezaCash < 0 ? 'text-red-600' : 'text-green-600') }}">{{ $bezaCash > 0.005 ? '+' : '' }}{{ number_format($bezaCash, 2) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-800 whitespace-nowrap">{{ number_format($session->closing_qr, 2) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600 whitespace-nowrap">{{ number_format($qrPos, 2) }}</td>
                                    <td class="px-4 py-3 text-right font-medium whitespace-nowrap {{ abs($bezaQr) < 0.005 ? 'text-gray-400' : ($bezaQr < 0 ? 'text-red-600' : 'text-green-600') }}">{{ $bezaQr > 0.005 ? '+' : '' }}{{ number_format($bezaQr, 2) }}</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <a href="{{ route('daily-session.report', $session) }}" target="_blank" class="text-amber-600 hover:underline">Lihat</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                @endif
            </div>

            {{ $sessions->links() }}
        </div>
    </div>
</x-app-layout>
