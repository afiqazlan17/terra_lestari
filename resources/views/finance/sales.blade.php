<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Finance</h2>
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
                    Laporan jualan bagi {{ $from->format('d M Y') }} hingga {{ $to->format('d M Y') }}
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
                        <p class="p-6 text-sm text-gray-400">Tiada jualan dalam tempoh ni.</p>
                    @else
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($topProducts as $product)
                                    <tr>
                                        <td class="px-6 py-3 text-gray-700">
                                            {{ $product->product_name }}
                                            <span class="text-gray-400">({{ $product->qty_sold }} unit)</span>
                                        </td>
                                        <td class="px-6 py-3 text-right font-medium text-gray-900">RM {{ number_format($product->revenue, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-3 bg-gray-50 text-sm font-semibold text-gray-600">Ikut Kaedah Bayaran</div>
                    @if ($salesByPaymentMethod->isEmpty())
                        <p class="p-6 text-sm text-gray-400">Tiada jualan dalam tempoh ni.</p>
                    @else
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($salesByPaymentMethod as $row)
                                    <tr>
                                        <td class="px-6 py-3 text-gray-700">
                                            {{ \App\Models\Order::PAYMENT_METHODS[$row->payment_method] ?? $row->payment_method }}
                                            <span class="text-gray-400">({{ $row->order_count }} order)</span>
                                        </td>
                                        <td class="px-6 py-3 text-right font-medium text-gray-900">RM {{ number_format($row->total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-3 bg-gray-50 text-sm font-semibold text-gray-600">Ikut Jenis Order</div>
                    @if ($salesByOrderType->isEmpty())
                        <p class="p-6 text-sm text-gray-400">Tiada jualan dalam tempoh ni.</p>
                    @else
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($salesByOrderType as $row)
                                    <tr>
                                        <td class="px-6 py-3 text-gray-700">
                                            {{ \App\Models\Order::TYPES[$row->order_type] ?? $row->order_type }}
                                            <span class="text-gray-400">({{ $row->order_count }} order)</span>
                                        </td>
                                        <td class="px-6 py-3 text-right font-medium text-gray-900">RM {{ number_format($row->total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-3 bg-gray-50 text-sm font-semibold text-gray-600">Jualan Harian</div>
                @if ($dailyBreakdown->isEmpty())
                    <p class="p-6 text-sm text-gray-400">Tiada jualan dalam tempoh ni.</p>
                @else
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs text-gray-500 uppercase">
                                <th class="px-6 py-2">Tarikh</th>
                                <th class="px-6 py-2">Bilangan Order</th>
                                <th class="px-6 py-2 text-right">Jualan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($dailyBreakdown as $day)
                                <tr>
                                    <td class="px-6 py-3 text-gray-700">{{ \Illuminate\Support\Carbon::parse($day->day)->translatedFormat('D, d M Y') }}</td>
                                    <td class="px-6 py-3 text-gray-600">{{ $day->order_count }}</td>
                                    <td class="px-6 py-3 text-right font-medium text-gray-900">RM {{ number_format($day->total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
