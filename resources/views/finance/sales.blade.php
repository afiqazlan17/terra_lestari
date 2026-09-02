<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Jualan') }}</h2>
    </x-slot>

    <style>
        @media print {
            .no-print { display: none !important; }
        }
    </style>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="no-print">
                @include('finance.partials.subnav')
                @include('finance.partials.filter')
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 mb-4 no-print">
                <p class="text-sm text-gray-500">
                    Laporan jualan bagi {{ $from->format('d F Y') }} hingga {{ $to->format('d F Y') }}
                </p>
                <div class="flex gap-2">
                    <a href="{{ route('finance.sales.export', request()->query()) }}"
                        class="inline-flex items-center gap-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium px-3 py-1.5 rounded-lg">
                        Export CSV
                    </a>
                    <button type="button" onclick="window.print()"
                        class="inline-flex items-center gap-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium px-3 py-1.5 rounded-lg">
                        Print
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Jumlah Jualan</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">RM {{ number_format($totalSales, 2) }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Bilangan Order</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($orderCount) }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Purata Nilai Order</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">RM {{ number_format($averageOrderValue, 2) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-3 bg-gray-50 text-sm font-semibold text-gray-600">Menu Paling Laku</div>
                    @if ($topProducts->isEmpty())
                        <p class="p-6 text-sm text-gray-400">Tiada jualan dalam tempoh ini.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100 text-sm">
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($topProducts as $product)
                                        <tr>
                                            <td class="px-6 py-3 text-gray-700">
                                                {{ $product->product_name }}
                                                <span class="text-gray-400 whitespace-nowrap">({{ $product->qty_sold }} unit)</span>
                                            </td>
                                            <td class="px-6 py-3 text-right font-medium text-gray-900 whitespace-nowrap">RM {{ number_format($product->revenue, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-3 bg-gray-50 text-sm font-semibold text-gray-600">Ikut Kaedah Bayaran</div>
                    @if ($salesByPaymentMethod->isEmpty())
                        <p class="p-6 text-sm text-gray-400">Tiada jualan dalam tempoh ini.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100 text-sm">
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($salesByPaymentMethod as $row)
                                        <tr>
                                            <td class="px-6 py-3 text-gray-700 whitespace-nowrap">
                                                {{ \App\Models\Order::PAYMENT_METHODS[$row->payment_method] ?? $row->payment_method }}
                                                <span class="text-gray-400">({{ $row->order_count }} order)</span>
                                            </td>
                                            <td class="px-6 py-3 text-right font-medium text-gray-900 whitespace-nowrap">RM {{ number_format($row->total, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-3 bg-gray-50 text-sm font-semibold text-gray-600">Jualan Harian</div>
                @if ($dailyBreakdown->isEmpty())
                    <p class="p-6 text-sm text-gray-400">Tiada jualan dalam tempoh ini.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs text-gray-500 uppercase">
                                    <th class="px-6 py-2 whitespace-nowrap">Tarikh</th>
                                    <th class="px-6 py-2 whitespace-nowrap">Bilangan Order</th>
                                    <th class="px-6 py-2 text-right whitespace-nowrap">Jualan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($dailyBreakdown as $day)
                                    <tr>
                                        <td class="px-6 py-3 text-gray-700 whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($day->day)->translatedFormat('l, d F Y') }}</td>
                                        <td class="px-6 py-3 text-gray-600 whitespace-nowrap">{{ $day->order_count }}</td>
                                        <td class="px-6 py-3 text-right font-medium text-gray-900 whitespace-nowrap">RM {{ number_format($day->total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden mt-6">
                <div class="px-6 py-3 bg-gray-50 flex items-center justify-between">
                    <span class="text-sm font-semibold text-gray-600">Senarai Order</span>
                    @if ($recentOrders->count() >= 100)
                        <span class="text-xs text-gray-400">Menunjukkan 100 order terkini dalam tempoh ini</span>
                    @endif
                </div>
                @if ($recentOrders->isEmpty())
                    <p class="p-6 text-sm text-gray-400">Tiada jualan dalam tempoh ini.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs text-gray-500 uppercase">
                                    <th class="px-6 py-2 whitespace-nowrap">No. Resit</th>
                                    <th class="px-6 py-2 whitespace-nowrap">Tarikh/Masa</th>
                                    <th class="px-6 py-2">Item</th>
                                    <th class="px-6 py-2 whitespace-nowrap">Bayaran</th>
                                    <th class="px-6 py-2 text-right whitespace-nowrap">Jumlah</th>
                                    <th class="px-6 py-2 text-right whitespace-nowrap no-print">Resit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($recentOrders as $order)
                                    <tr>
                                        <td class="px-6 py-3 text-gray-700 whitespace-nowrap">{{ $order->order_number }}</td>
                                        <td class="px-6 py-3 text-gray-600 whitespace-nowrap">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="px-6 py-3 text-gray-600">
                                            <ul class="list-disc list-inside space-y-0.5">
                                                @foreach ($order->items as $item)
                                                    <li class="whitespace-nowrap">{{ $item->product_name }} x{{ $item->qty }}</li>
                                                @endforeach
                                            </ul>
                                        </td>
                                        <td class="px-6 py-3 text-gray-600 whitespace-nowrap">{{ $order->paymentMethodLabel() }}</td>
                                        <td class="px-6 py-3 text-right font-medium text-gray-900 whitespace-nowrap">RM {{ number_format($order->total, 2) }}</td>
                                        <td class="px-6 py-3 text-right whitespace-nowrap no-print">
                                            <a href="{{ route('orders.receipt', $order) }}" target="_blank" class="text-amber-600 hover:underline">Lihat Resit</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            @if ($voidedOrders->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden mt-6 border border-red-100">
                    <div class="px-6 py-3 bg-red-50 flex items-center justify-between">
                        <span class="text-sm font-semibold text-red-700">Order Dibatalkan (Void)</span>
                        <span class="text-xs text-red-400">Tidak dikira dalam Jualan</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs text-gray-500 uppercase">
                                    <th class="px-6 py-2 whitespace-nowrap">No. Resit</th>
                                    <th class="px-6 py-2 whitespace-nowrap">Jumlah</th>
                                    <th class="px-6 py-2 whitespace-nowrap">Dibatalkan pada</th>
                                    <th class="px-6 py-2 whitespace-nowrap">Oleh</th>
                                    <th class="px-6 py-2">Sebab</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($voidedOrders as $order)
                                    <tr>
                                        <td class="px-6 py-3 text-gray-700 whitespace-nowrap">{{ $order->order_number }}</td>
                                        <td class="px-6 py-3 text-gray-600 whitespace-nowrap">RM {{ number_format($order->total, 2) }}</td>
                                        <td class="px-6 py-3 text-gray-600 whitespace-nowrap">{{ $order->voided_at?->format('d/m/Y H:i') }}</td>
                                        <td class="px-6 py-3 text-gray-600 whitespace-nowrap">{{ $order->voidedBy?->name ?? '-' }}</td>
                                        <td class="px-6 py-3 text-gray-600">{{ $order->void_reason }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
