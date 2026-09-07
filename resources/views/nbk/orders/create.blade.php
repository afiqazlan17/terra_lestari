<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $order ? 'Edit Order NBK #'.$order->displayNumber() : 'Upload Invoice NBK' }}
            </h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('nbk.products.index') }}" class="border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-semibold px-4 py-2 rounded-lg">
                    Urus Katalog
                </a>
                <a href="{{ route('nbk.orders.index') }}" class="border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-semibold px-4 py-2 rounded-lg">
                    Senarai Order
                </a>
                <a href="{{ route('nbk.index') }}" class="text-gray-500 hover:text-gray-700 text-sm px-2">
                    &larr; Kembali
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
                <form method="POST" action="{{ $order ? route('nbk.orders.update', $order) : route('nbk.orders.store') }}"
                    x-data="nbkCalculator(@js($products->map(fn ($p) => ['id' => $p->id, 'unit_cost' => (float) $p->unit_cost, 'sell_price' => (float) ($p->sell_price ?? 0), 'min_qty' => $p->min_qty, 'orderable' => $p->isOrderable()])), @js($qtyByProductId), {{ $order ? 'true' : 'false' }})">
                    @csrf
                    @if ($order)
                        @method('PATCH')
                    @endif

                    @unless ($order)
                        <div id="invois-upload" class="bg-white shadow-sm sm:rounded-lg p-4 mb-4" x-show="! showTable">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Upload Invois NBK</label>
                            <p class="text-xs text-gray-500 mb-3">Snap/upload invois pembelian NBK - produk, kuantiti dan tarikh akan dikesan automatik untuk semakan.</p>
                            <div class="flex items-center gap-3 flex-wrap">
                                <input type="file" accept="image/*,.pdf" x-ref="invoiceFile" @change="uploadInvoice($event)" class="text-sm">
                                <span x-show="invoiceStatus" x-text="invoiceStatus" class="text-xs text-gray-500"></span>
                            </div>
                            <div class="mt-3 pt-3 border-t border-gray-100">
                                <button type="button" @click="openManual()" class="text-sm text-gray-600 hover:text-gray-800 font-medium hover:underline">
                                    Atau isi manual (tanpa upload) &rarr;
                                </button>
                            </div>
                        </div>

                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4 flex items-center justify-between" x-show="showTable">
                            <div>
                                <p class="text-sm font-medium text-amber-800">Order untuk stok: <span x-text="orderDateLabel"></span></p>
                                <p class="text-xs text-amber-700 mt-0.5" x-show="invoiceStatus" x-text="invoiceStatus"></p>
                                <p class="text-xs text-amber-700 mt-0.5" x-show="invoiceUnmatched.length > 0">
                                    Tidak dapat padan dengan katalog, sila isi manual: <span x-text="invoiceUnmatched.map(i => i.name + ' (' + i.qty + ')').join(', ')"></span>
                                </p>
                            </div>
                            <button type="button" @click="reset()" class="text-xs text-gray-500 hover:text-gray-700 hover:underline shrink-0 ml-3">
                                &larr; Upload lain / mula semula
                            </button>
                        </div>

                        <input type="hidden" name="order_date" :value="orderDate">
                    @endunless

                    @if ($order)
                        <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-4 flex items-center gap-3">
                            <label class="text-sm text-gray-600">Tarikh Order:</label>
                            <input type="date" name="order_date" value="{{ $order->order_date->toDateString() }}" required class="rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                    @endif

                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden" x-show="showTable" @if(! $order) x-cloak @endif>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100 text-sm">
                                <thead class="bg-gray-50">
                                    <tr class="text-left text-xs text-gray-500 uppercase">
                                        <th class="px-3 py-2 w-10">No</th>
                                        <th class="px-3 py-2">Produk</th>
                                        <th class="px-3 py-2 text-right whitespace-nowrap">Harga/Kuantiti</th>
                                        <th class="px-3 py-2 text-right whitespace-nowrap">Kuantiti Minimum</th>
                                        <th class="px-3 py-2 w-32">Kuantiti Order</th>
                                        <th class="px-3 py-2 text-right whitespace-nowrap">Harga Beli (RM)</th>
                                        <th class="px-3 py-2 text-right whitespace-nowrap">Harga Jual (RM)</th>
                                        <th class="px-3 py-2 text-right whitespace-nowrap">Untung (RM)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($products as $index => $product)
                                        <tr class="{{ ! $product->isOrderable() ? 'bg-gray-50' : '' }}">
                                            <td class="px-3 py-2 text-gray-400">{{ $index + 1 }}</td>
                                            <td class="px-3 py-2 {{ $product->isOrderable() ? 'text-gray-800' : 'text-gray-400' }}">
                                                {{ $product->name }}
                                                @unless ($product->isOrderable())
                                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs {{ $product->status === \App\Models\NbkProduct::STATUS_EXCLUDED ? 'bg-gray-200 text-gray-600' : 'bg-red-100 text-red-700' }} ml-1">{{ $product->statusLabel() }}</span>
                                                @endunless
                                                <input type="hidden" name="items[{{ $index }}][nbk_product_id]" value="{{ $product->id }}">
                                            </td>
                                            <td class="px-3 py-2 text-right text-gray-600 tabular-nums whitespace-nowrap">
                                                @if ($product->isOrderable())
                                                    RM <span x-text="prices[{{ $product->id }}].toFixed(2)"></span>
                                                    <span x-show="priceUpdated[{{ $product->id }}]" x-cloak class="text-amber-600 text-xs ml-1" title="Dikemaskini dari invois">&#9998;</span>
                                                @else
                                                    &mdash;
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-right text-gray-600 tabular-nums whitespace-nowrap">{{ $product->isOrderable() ? $product->min_qty : '—' }}</td>
                                            <td class="px-3 py-2">
                                                @if ($product->isOrderable())
                                                    <input type="number" min="0" step="1" name="items[{{ $index }}][qty_ordered]"
                                                        x-model.number="qty[{{ $product->id }}]"
                                                        class="w-20 rounded-md shadow-sm text-sm"
                                                        :class="belowMinimum({{ $product->id }}) ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300'">
                                                    <p x-show="belowMinimum({{ $product->id }})" x-cloak class="text-xs text-red-500 mt-1">Bawah kuantiti minimum</p>
                                                @else
                                                    <input type="hidden" name="items[{{ $index }}][qty_ordered]" value="0">
                                                    <span class="text-xs text-gray-400">Tidak boleh order</span>
                                                @endif
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

                    <div class="bg-white shadow-sm sm:rounded-lg p-4 mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm" x-show="showTable" @if(! $order) x-cloak @endif>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Jumlah Kuantiti Order</p>
                            <p class="text-lg font-semibold text-gray-800" x-text="totalQty()"></p>
                        </div>
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

                    <div class="mt-4 flex items-center gap-3" x-show="showTable" @if(! $order) x-cloak @endif>
                        <x-primary-button type="submit">{{ $order ? 'Kemaskini Memo' : 'Confirm' }}</x-primary-button>
                        @if ($order)
                            <a href="{{ route('nbk.orders.show', $order) }}" class="text-sm text-gray-500 hover:underline">Batal</a>
                        @endif
                    </div>
                </form>
            @endif
        </div>
    </div>

    <script>
        function nbkCalculator(products, initialQty, isEdit) {
            const byId = {};
            products.forEach(p => { byId[p.id] = p; });

            const tomorrowIso = () => {
                const d = new Date();
                d.setDate(d.getDate() + 1);
                return d.toISOString().slice(0, 10);
            };

            const formatLabel = (iso) => {
                if (! iso) return '';
                const d = new Date(iso + 'T00:00:00');
                return new Intl.DateTimeFormat('ms-MY', { day: 'numeric', month: 'long', year: 'numeric' }).format(d);
            };

            return {
                qty: Object.fromEntries(products.map(p => [p.id, initialQty[p.id] ?? 0])),
                prices: Object.fromEntries(products.map(p => [p.id, p.unit_cost])),
                priceUpdated: Object.fromEntries(products.map(p => [p.id, false])),
                invoiceStatus: '',
                invoiceUnmatched: [],
                showTable: isEdit,
                orderDate: tomorrowIso(),
                orderDateLabel: '',
                init() {
                    this.orderDateLabel = formatLabel(this.orderDate);
                },
                openManual() {
                    this.showTable = true;
                    this.orderDate = tomorrowIso();
                    this.orderDateLabel = formatLabel(this.orderDate);
                },
                reset() {
                    this.showTable = false;
                    this.invoiceStatus = '';
                    this.invoiceUnmatched = [];
                    this.qty = Object.fromEntries(products.map(p => [p.id, 0]));
                    this.priceUpdated = Object.fromEntries(products.map(p => [p.id, false]));
                    if (this.$refs.invoiceFile) {
                        this.$refs.invoiceFile.value = '';
                    }
                },
                async uploadInvoice(event) {
                    const file = event.target.files[0];
                    if (! file) return;

                    this.invoiceStatus = 'Membaca invois...';
                    this.invoiceUnmatched = [];

                    try {
                        const formData = new FormData();
                        formData.append('invoice', file);

                        const res = await fetch('{{ route('nbk.invoices.extract') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });

                        const data = await res.json();

                        if (! res.ok) {
                            this.invoiceStatus = data.error || 'Gagal baca invois. Sila isi manual.';
                            return;
                        }

                        (data.matched || []).forEach((item) => {
                            if (item.nbk_product_id in this.qty) {
                                this.qty[item.nbk_product_id] = item.qty;
                                if (typeof item.unit_cost === 'number' && item.unit_cost !== this.prices[item.nbk_product_id]) {
                                    this.prices[item.nbk_product_id] = item.unit_cost;
                                    this.priceUpdated[item.nbk_product_id] = true;
                                }
                            }
                        });
                        this.invoiceUnmatched = data.unmatched || [];

                        if (data.order_date) {
                            this.orderDate = data.order_date;
                            this.orderDateLabel = formatLabel(this.orderDate);
                        }

                        const matchedCount = (data.matched || []).length;
                        const priceUpdates = data.price_updates || 0;
                        this.invoiceStatus = matchedCount > 0
                            ? `${matchedCount} produk diisi automatik - sila semak sebelum hantar.`
                                + (priceUpdates > 0 ? ` ${priceUpdates} harga katalog dikemaskini ikut invois.` : '')
                            : 'Tiada produk dikesan. Sila isi manual.';
                        this.showTable = true;
                    } catch (err) {
                        this.invoiceStatus = 'Gagal baca invois. Sila isi manual.';
                    }
                },
                belowMinimum(id) {
                    const q = this.qty[id] || 0;
                    return q > 0 && q < byId[id].min_qty;
                },
                buyTotal(id) {
                    return (this.qty[id] || 0) * this.prices[id];
                },
                sellTotal(id) {
                    return (this.qty[id] || 0) * byId[id].sell_price;
                },
                profit(id) {
                    return this.sellTotal(id) - this.buyTotal(id);
                },
                totalQty() {
                    return products.reduce((sum, p) => sum + (this.qty[p.id] || 0), 0);
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
