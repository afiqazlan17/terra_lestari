<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Finance</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @include('finance.partials.subnav')
            @include('finance.partials.filter')

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Cash Masuk</p>
                    <p class="mt-1 text-xl font-semibold text-green-600">RM {{ number_format($totalMasuk, 2) }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Cash Keluar</p>
                    <p class="mt-1 text-xl font-semibold text-red-600">RM {{ number_format($totalKeluar, 2) }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Net Cash</p>
                    <p class="mt-1 text-xl font-semibold {{ $netCash >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        RM {{ number_format($netCash, 2) }}
                    </p>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                @if ($entries->isEmpty())
                    <p class="p-8 text-center text-gray-400">Tiada transaksi dalam tempoh ni.</p>
                @else
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs text-gray-500 uppercase">
                                <th class="px-6 py-3">Tarikh</th>
                                <th class="px-6 py-3">Keterangan</th>
                                <th class="px-6 py-3 text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($entries as $entry)
                                <tr>
                                    <td class="px-6 py-3 text-gray-600">{{ \Illuminate\Support\Carbon::parse($entry['date'])->format('d M Y') }}</td>
                                    <td class="px-6 py-3 text-gray-800">{{ $entry['description'] }}</td>
                                    <td class="px-6 py-3 text-right font-medium {{ $entry['type'] === 'masuk' ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $entry['type'] === 'masuk' ? '+' : '-' }} RM {{ number_format($entry['amount'], 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
