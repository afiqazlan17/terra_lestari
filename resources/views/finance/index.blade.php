<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Finance</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @include('finance.partials.subnav')
            @include('finance.partials.filter')

            <p class="text-sm text-gray-500 mb-4">
                Untung rugi bagi {{ $from->format('d M Y') }} hingga {{ $to->format('d M Y') }}
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Jualan (Revenue)</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">RM {{ number_format($revenue, 2) }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Untung Kasar</p>
                    <p class="mt-1 text-2xl font-semibold {{ $grossProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        RM {{ number_format($grossProfit, 2) }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Jualan tolak kos bahan mentah</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Untung Bersih</p>
                    <p class="mt-1 text-2xl font-semibold {{ $netProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        RM {{ number_format($netProfit, 2) }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Jualan tolak semua expense</p>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="flex items-center justify-between mb-1">
                    <p class="text-sm font-medium text-gray-700">Food Cost</p>
                    <span class="text-xs font-medium px-2 py-1 rounded-full {{ $foodCostPercent <= 35 ? 'bg-green-100 text-green-800' : ($foodCostPercent <= 40 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                        {{ number_format($foodCostPercent, 1) }}% daripada jualan
                    </span>
                </div>
                <p class="text-xl font-semibold text-gray-900">RM {{ number_format($foodCost, 2) }}</p>
                <p class="text-xs text-gray-400 mt-2">
                    Nisbah sihat untuk kedai makan biasanya antara 28% hingga 35%. Lagi rendah, lagi baik margin.
                </p>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden mb-6">
                <div class="px-6 py-3 bg-gray-50 text-sm font-semibold text-gray-600">Expense Ikut Kategori</div>
                @if ($purchasesByCategory->isEmpty())
                    <p class="p-6 text-sm text-gray-400">Tiada expense direkodkan dalam tempoh ni.</p>
                @else
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <tbody class="divide-y divide-gray-100">
                            @foreach (\App\Models\Purchase::CATEGORIES as $key => $label)
                                @if (isset($purchasesByCategory[$key]))
                                    <tr>
                                        <td class="px-6 py-3 text-gray-700">{{ $label }}</td>
                                        <td class="px-6 py-3 text-right font-medium text-gray-900">RM {{ number_format($purchasesByCategory[$key], 2) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                            <tr class="bg-gray-50">
                                <td class="px-6 py-3 font-semibold text-gray-800">Jumlah Expense</td>
                                <td class="px-6 py-3 text-right font-semibold text-gray-900">RM {{ number_format($totalExpenses, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-500">Modal Awal Masuk (tempoh ni)</p>
                <p class="mt-1 text-xl font-semibold text-gray-900">RM {{ number_format($totalCapitalInjected, 2) }}</p>
                <p class="text-xs text-gray-400 mt-1">Ini suntikan dana owner, bukan revenue operasi, jadi tak dikira sebagai jualan.</p>
            </div>
        </div>
    </div>
</x-app-layout>
