<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kalkulator Order NBK</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('nbk.products.index') }}" class="border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-semibold px-4 py-2 rounded-lg">
                    Urus Katalog
                </a>
                <a href="{{ route('nbk.orders.index') }}" class="border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-semibold px-4 py-2 rounded-lg">
                    Sejarah Order
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if ($products->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-400">
                    Belum ada produk dalam katalog NBK. <a href="{{ route('nbk.products.index') }}" class="text-amber-600 hover:underline">Tambah produk dulu</a>.
                </div>
            @else
                <form method="POST" action="{{ route('nbk.orders.store') }}"
                    x-data="nbkCalculator(@js($products->map(fn ($p) => ['id' => $p->id, 'unit_cost' => (float) $p->unit_cost, 'sell_price' => (float) ($p->sell_price ?? 0), 'min_qty' => $p->min_qty])))">
                    @csrf

                    <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-4 flex items-center gap-3">
                        <label class="text-sm text-gray-600">Tarikh Order:</label>
                        <input type="date" name="order_date" value="{{ now()->toDateString() }}" required class="rounded-md border-gray-300 shadow-sm text-sm">
                    </div>

                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100 text-sm">
                                <thead class="bg-gray-50">
                                    <tr class="text-left text-xs text-gray-500 uppercase">
                                        <th class="px-3 py-2 w-10">No</th>
                                        <th class="px-3 py-2">Produk</th>
                                        <th class="px-3 py-2 text-right whitespace-nowrap">Harga/Kuantiti</th>
                                        <th class="px-3 py-2 text-right whitespace-nowrap">Kuantiti Minimum</th>
                                        <th class="px-3 py-2 w-28">Kuantiti Order</th>
                                        <th class="px-3 py-2 text-right whitespace-nowrap">Harga Beli (RM)</th>
                                        <th class="px-3 py-2 text-right whitespace-nowrap">Harga Jual (RM)</th>
                                        <th class="px-3 py-2 text-right whitespace-nowrap">Untung (RM)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($products as $index => $product)
                                        <tr>
                                            <td class="px-3 py-2 text-gray-400">{{ $index + 1 }}</td>
                                            <td class="px-3 py-2 text-gray-800">
                                                {{ $product->name }}
                                                <input type="hidden" name="items[{{ $index }}][nbk_product_id]" value="{{ $product->id }}">
                                            </td>
                                            <td class="px-3 py-2 text-right text-gray-600 tabular-nums whitespace-nowrap">RM {{ number_format($product->unit_cost, 2) }}</td>
                                            <td class="px-3 py-2 text-right text-gray-600 tabular-nums whitespace-nowrap">{{ $product->min_qty }}</td>
                                            <td class="px-3 py-2">
                                                <input type="number" min="0" step="1" name="items[{{ $index }}][qty_ordered]"
                                                    x-model.number="qty[{{ $product->id }}]"
                                                    class="w-20 rounded-md border-gray-300 shadow-sm text-sm">
                                            </td>
                                            <td class="px-3 py-2 text-right tabular-nums whitespace-nowrap" x-text="'RM ' + buyTotal({{ $product->id }}).toFixed(2)"></td>
                                            <td class="px-3 py-2 text-right tabular-nums whitespace-nowrap" x-text="'RM ' + sellTotal({{ $product->id }}).toFixed(2)"></td>
                                            <td class="px-3 py-2 text-right tabular-nums whitespace-nowrap"
                                                :class="profit({{ $product->id }}) < 0 ? 'text-red-500' : 'text-green-600'"
                                                x-text="'RM ' + profit({{ $product->id }}).toFixed(2)"></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-white shadow-sm sm:rounded-lg p-4 mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Total Harga Beli</p>
                            <p class="text-lg font-semibold text-gray-800" x-text="'RM ' + totalBuy().toFixed(2)"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Total Harga Jual</p>
                            <p class="text-lg font-semibold text-gray-800" x-text="'RM ' + totalSell().toFixed(2)"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Total Untung</p>
                            <p class="text-lg font-semibold" :class="totalProfit() < 0 ? 'text-red-500' : 'text-green-600'" x-text="'RM ' + totalProfit().toFixed(2)"></p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <x-primary-button type="submit">Jana Memo Pembayaran</x-primary-button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <script>
        function nbkCalculator(products) {
            const byId = {};
            products.forEach(p => { byId[p.id] = p; });

            return {
                qty: Object.fromEntries(products.map(p => [p.id, p.min_qty])),
                buyTotal(id) {
                    return (this.qty[id] || 0) * byId[id].unit_cost;
                },
                sellTotal(id) {
                    return (this.qty[id] || 0) * byId[id].sell_price;
                },
                profit(id) {
                    return this.sellTotal(id) - this.buyTotal(id);
                },
                totalBuy() {
                    return products.reduce((sum, p) => sum + this.buyTotal(p.id), 0);
                },
                totalSell() {
                    return products.reduce((sum, p) => sum + this.sellTotal(p.id), 0);
                },
                totalProfit() {
                    return this.totalSell() - this.totalBuy();
                },
            };
        }
    </script>
</x-app-layout>
