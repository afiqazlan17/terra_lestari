<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sejarah Order NBK</h2>
            <a href="{{ route('nbk.orders.create') }}" class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                + Buat Order
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                @if ($orders->isEmpty())
                    <p class="p-8 text-center text-gray-400">Belum ada order NBK direkodkan.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs text-gray-500 uppercase">
                                    <th class="px-4 py-3 whitespace-nowrap">Tarikh Order</th>
                                    <th class="px-4 py-3 whitespace-nowrap">Dijana Oleh</th>
                                    <th class="px-4 py-3 text-right whitespace-nowrap">Total Beli</th>
                                    <th class="px-4 py-3 text-right whitespace-nowrap">Total Untung</th>
                                    <th class="px-4 py-3 whitespace-nowrap">Status</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($orders as $order)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $order->order_date->format('d F Y') }}</td>
                                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $order->createdBy->name }}</td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-900 whitespace-nowrap">RM {{ number_format($order->total_buy, 2) }}</td>
                                        <td class="px-4 py-3 text-right tabular-nums whitespace-nowrap {{ $order->total_profit < 0 ? 'text-red-500' : 'text-green-600' }}">RM {{ number_format($order->total_profit, 2) }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if ($order->isPaid())
                                                <span class="inline-flex rounded-full px-2 py-1 text-xs bg-green-100 text-green-800">Dibayar</span>
                                            @else
                                                <span class="inline-flex rounded-full px-2 py-1 text-xs bg-amber-100 text-amber-700">Belum Dibayar</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <a href="{{ route('nbk.orders.show', $order) }}" class="text-amber-600 hover:underline text-xs">Lihat Memo</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            <div class="mt-4">
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
