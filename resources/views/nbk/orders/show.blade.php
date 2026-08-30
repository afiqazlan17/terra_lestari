<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Memo Pembayaran NBK #{{ $order->id }}</h2>
    </x-slot>

    <style>
        @media print {
            .no-print { display: none !important; }
        }
    </style>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="flex justify-end gap-2 no-print">
                @if (! $order->isPaid() && auth()->user()->hasFullAccess())
                    <form method="POST" action="{{ route('nbk.orders.paid', $order) }}" onsubmit="return confirm('Tandai memo ini sebagai dibayar? Ia akan direkodkan terus dalam Belian.')">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="inline-flex items-center gap-1.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-3 py-1.5 rounded-lg">
                            Tandai Dibayar
                        </button>
                    </form>
                @endif
                <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium px-3 py-1.5 rounded-lg">
                    Print / Simpan PDF
                </button>
                <a href="{{ route('nbk.orders.index') }}"
                    class="inline-flex items-center gap-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium px-3 py-1.5 rounded-lg">
                    Kembali
                </a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <img src="{{ asset('images/logo.png') }}" alt="Sajian Baginda" class="max-w-[160px] mb-2">
                        <p class="text-sm text-gray-500">Memo Pembayaran - Vendor NBK (Nasi Berlauk Kelantan)</p>
                    </div>
                    <div class="text-right text-sm text-gray-500">
                        <p>Memo #{{ $order->id }}</p>
                        <p>Tarikh Order: {{ $order->order_date->format('d F Y') }}</p>
                        <p>Dijana oleh: {{ $order->createdBy->name }}</p>
                    </div>
                </div>

                @if ($order->isPaid())
                    <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg p-3 mb-4">
                        Dibayar oleh {{ $order->paidBy->name }} pada {{ $order->paid_at->format('d F Y, H:i') }}.
                        Direkodkan dalam Belian (#{{ $order->purchase_id }}).
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs text-gray-500 uppercase">
                                <th class="px-3 py-2 w-10">No</th>
                                <th class="px-3 py-2">Produk</th>
                                <th class="px-3 py-2 text-right whitespace-nowrap">Harga/Kuantiti</th>
                                <th class="px-3 py-2 text-right whitespace-nowrap">Kuantiti Order</th>
                                <th class="px-3 py-2 text-right whitespace-nowrap">Harga Beli (RM)</th>
                                <th class="px-3 py-2 text-right whitespace-nowrap">Harga Jual (RM)</th>
                                <th class="px-3 py-2 text-right whitespace-nowrap">Untung (RM)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($order->items as $index => $item)
                                <tr>
                                    <td class="px-3 py-2 text-gray-400">{{ $index + 1 }}</td>
                                    <td class="px-3 py-2 text-gray-800">{{ $item->product_name }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600 tabular-nums whitespace-nowrap">RM {{ number_format($item->unit_cost, 2) }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600 tabular-nums whitespace-nowrap">{{ $item->qty_ordered }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums whitespace-nowrap">RM {{ number_format($item->buy_total, 2) }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums whitespace-nowrap">RM {{ number_format($item->sell_total, 2) }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums whitespace-nowrap {{ $item->profit < 0 ? 'text-red-500' : 'text-green-600' }}">RM {{ number_format($item->profit, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6 pt-4 border-t border-gray-200 text-sm">
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Total Harga Beli</p>
                        <p class="text-lg font-semibold text-gray-800">RM {{ number_format($order->total_buy, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Total Harga Jual</p>
                        <p class="text-lg font-semibold text-gray-800">RM {{ number_format($order->total_sell, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Total Untung</p>
                        <p class="text-lg font-semibold {{ $order->total_profit < 0 ? 'text-red-500' : 'text-green-600' }}">RM {{ number_format($order->total_profit, 2) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
