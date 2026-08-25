<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Belian Barang</h2>
            <a href="{{ route('purchases.create') }}" class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                + Rekod Belian
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                @if ($purchases->isEmpty())
                    <p class="p-8 text-center text-gray-400">Belum ada belian direkodkan.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs text-gray-500 uppercase">
                                    <th class="px-4 py-3 whitespace-nowrap">Tarikh</th>
                                    <th class="px-4 py-3">Keterangan</th>
                                    <th class="px-4 py-3 whitespace-nowrap">Pembekal</th>
                                    <th class="px-4 py-3 whitespace-nowrap">Resit</th>
                                    <th class="px-4 py-3 text-right whitespace-nowrap">Jumlah</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($purchases as $purchase)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $purchase->purchase_date->format('d M Y') }}</td>
                                        <td class="px-4 py-3 text-gray-800">
                                            {{ $purchase->description }}
                                            @if ($purchase->notes)
                                                <p class="text-xs text-gray-400">{{ $purchase->notes }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $purchase->supplier_name ?? '-' }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if ($purchase->receipt_path)
                                                <a href="{{ Storage::url($purchase->receipt_path) }}" target="_blank" class="text-amber-600 hover:underline">Lihat</a>
                                            @else
                                                <span class="text-gray-300">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-900 whitespace-nowrap">RM {{ number_format($purchase->amount, 2) }}</td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <form method="POST" action="{{ route('purchases.destroy', $purchase) }}" onsubmit="return confirm('Padam belian ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:underline text-xs">Padam</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            <div class="mt-4">
                {{ $purchases->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
