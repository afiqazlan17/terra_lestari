<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                POS
            </h2>
            <div x-data class="flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full"
                :class="$store.connectivity.online ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                <span class="w-2 h-2 rounded-full" :class="$store.connectivity.online ? 'bg-green-500' : 'bg-red-500'"></span>
                <span x-text="$store.connectivity.online ? 'Online' : 'Offline'"></span>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @unless ($session)
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <p class="font-medium text-amber-800">Sila masukkan wang tunai pembukaan untuk hari ini</p>
                    </div>
                    <form method="POST" action="{{ route('daily-session.open') }}" class="flex flex-wrap items-end gap-2">
                        @csrf
                        <div>
                            <label class="block text-xs text-amber-700 mb-1">Tunai Pembukaan (RM)</label>
                            <input type="text" inputmode="decimal" data-money-input name="opening_cash" required
                                class="rounded-md border-amber-300 shadow-sm text-sm w-32">
                        </div>
                        <x-primary-button type="submit">Buka Hari</x-primary-button>
                    </form>
                </div>
            @endunless

            <div x-data="posCart({{ $session ? 'true' : 'false' }})" x-init="init()">

                {{-- Offline / pending sync banner --}}
                <template x-if="! $store.connectivity.online">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-4 text-sm text-red-800 space-y-0.5">
                        <p>Tiada sambungan internet. Anda masih boleh key in dan ambil order seperti biasa.</p>
                        <p>Data order akan disimpan sementara di peranti ini.</p>
                        <p>Sebaik sahaja internet kembali, data order akan dihantar secara automatik.</p>
                    </div>
                </template>
                <template x-if="$store.connectivity.online && pendingOrders.length > 0">
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4 flex flex-wrap items-center justify-between gap-2 text-sm">
                        <span class="text-amber-800">
                            <span x-text="pendingOrders.length"></span> order belum disync<span x-show="syncing"> — sedang menghantar...</span>
                        </span>
                        <button type="button" @click="trySync()" :disabled="syncing"
                            class="text-amber-700 font-medium hover:underline disabled:opacity-50">
                            Sync Sekarang
                        </button>
                    </div>
                </template>
                <template x-if="syncMessage">
                    <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4 text-sm text-green-800" x-text="syncMessage"></div>
                </template>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Menu grid --}}
                <div class="lg:col-span-2 space-y-6">
                    @forelse ($categories as $category)
                        @if ($category->products->isNotEmpty())
                            <div>
                                <h3 class="text-sm font-semibold text-gray-500 uppercase mb-2">{{ $category->name }}</h3>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    @foreach ($category->products as $product)
                                        <button type="button"
                                            :disabled="! hasSession"
                                            @click="addItem({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->price }})"
                                            :class="hasSession ? 'hover:ring-2 hover:ring-amber-400 active:scale-95' : 'opacity-50 cursor-not-allowed'"
                                            class="bg-white shadow-sm rounded-lg p-4 text-left transition">
                                            <p class="font-medium text-gray-800">{{ $product->name }}</p>
                                            <p class="text-sm text-gray-500 mt-1">RM {{ number_format($product->price, 2) }}</p>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="bg-white shadow-sm rounded-lg p-8 text-center text-gray-500">
                            Belum ada menu. Owner boleh tambah menu di halaman <a href="{{ route('products.index') }}" class="text-amber-600 hover:underline">Menu</a>.
                        </div>
                    @endforelse
                </div>

                {{-- Cart --}}
                <div class="bg-white shadow-sm rounded-lg p-4 h-fit sticky top-6">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase mb-3">Order</h3>

                    <template x-if="items.length === 0 && hasSession">
                        <p class="text-sm text-gray-400 py-6 text-center">Tap menu untuk tambah item</p>
                    </template>
                    <template x-if="! hasSession">
                        <p class="text-sm text-gray-400 py-6 text-center">Buka hari untuk mula jual</p>
                    </template>

                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        <template x-for="(item, index) in items" :key="item.product_id">
                            <div class="flex items-center justify-between text-sm border-b border-gray-100 py-2">
                                <div>
                                    <p class="text-gray-800" x-text="item.name"></p>
                                    <p class="text-gray-400">RM <span x-text="item.price.toFixed(2)"></span></p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="decrement(index)" class="w-6 h-6 rounded bg-gray-100 hover:bg-gray-200">&minus;</button>
                                    <span x-text="item.qty" class="w-4 text-center"></span>
                                    <button type="button" @click="increment(index)" class="w-6 h-6 rounded bg-gray-100 hover:bg-gray-200">+</button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="mt-4 space-y-1 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal</span>
                            <span>RM <span x-text="subtotal().toFixed(2)"></span></span>
                        </div>
                        <div class="flex justify-between items-center text-gray-600">
                            <span>Diskaun</span>
                            <input type="number" step="0.01" min="0" x-model.number="discount" :disabled="! hasSession" class="w-20 text-right rounded-md border-gray-300 text-sm disabled:bg-gray-100">
                        </div>
                        <div class="flex justify-between font-semibold text-gray-900 text-base pt-1">
                            <span>Jumlah</span>
                            <span>RM <span x-text="total().toFixed(2)"></span></span>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-xs text-gray-500 mb-1">Jenis Order</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" @click="orderType = 'dine_in'" :disabled="! hasSession"
                                :class="orderType === 'dine_in' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-600'"
                                class="rounded-md py-2 text-sm font-medium transition disabled:opacity-50">
                                Dine In
                            </button>
                            <button type="button" @click="orderType = 'takeaway'" :disabled="! hasSession"
                                :class="orderType === 'takeaway' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-600'"
                                class="rounded-md py-2 text-sm font-medium transition disabled:opacity-50">
                                Take Away
                            </button>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-xs text-gray-500 mb-1">Cara Bayar</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" @click="paymentMethod = 'qr'" :disabled="! hasSession"
                                :class="paymentMethod === 'qr' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-600'"
                                class="rounded-md py-2 text-sm font-medium transition disabled:opacity-50">
                                QR / DuitNow
                            </button>
                            <button type="button" @click="paymentMethod = 'cash'" :disabled="! hasSession"
                                :class="paymentMethod === 'cash' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-600'"
                                class="rounded-md py-2 text-sm font-medium transition disabled:opacity-50">
                                Cash
                            </button>
                            <button type="button" disabled
                                class="rounded-md py-2 text-sm font-medium transition bg-gray-100 text-gray-400 opacity-50 cursor-not-allowed">
                                Kad Debit/Kad Kredit
                            </button>
                        </div>
                    </div>

                    <template x-if="window.SBPrinter && window.SBPrinter.isSupported()">
                        <div class="mt-3 flex items-center justify-between text-xs">
                            <span :class="printerConnected ? 'text-green-700' : 'text-gray-400'">
                                <span x-text="printerConnected ? 'Printer: Disambung' : 'Printer: Tiada sambungan'"></span>
                            </span>
                            <button type="button" x-show="! printerConnected" @click="connectPrinter()" :disabled="printerBusy"
                                class="text-amber-600 font-medium hover:underline disabled:opacity-50">
                                Sambung Printer
                            </button>
                        </div>
                    </template>

                    <button type="button" @click="openConfirm()" :disabled="! hasSession || items.length === 0 || submitting"
                        class="mt-4 w-full bg-amber-500 hover:bg-amber-600 disabled:bg-gray-300 text-white font-semibold py-3 rounded-lg transition">
                        <span x-text="submitting ? 'Menghantar...' : 'Checkout'"></span>
                    </button>
                    <button type="button" @click="items = []; discount = 0" x-show="items.length > 0"
                        class="mt-2 w-full border border-gray-300 text-gray-600 font-medium text-sm py-2 rounded-lg hover:bg-gray-50 transition">
                        Reset
                    </button>
                </div>
                </div>

                {{-- Receipt preview before the order is actually saved/printed --}}
                <template x-if="confirming">
                    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                        <div class="bg-white rounded-lg shadow-lg w-full max-w-sm p-6 font-mono">
                            <h1 class="text-center font-bold text-lg">SAJIAN BAGINDA</h1>
                            <p class="text-center text-xs text-gray-500">Warisan Rasa Pantai Timur</p>
                            <div class="border-t border-dashed border-gray-300 my-3"></div>
                            <p class="text-xs text-gray-500">
                                No. Resit: <span :class="! orderResult && 'italic'" x-text="orderResult ? orderResult.orderNumber : 'Belum dijana'"></span><br>
                                Tarikh: <span x-text="confirmDateStr"></span><br>
                                Jenis: <span x-text="orderType === 'dine_in' ? 'Dine In' : 'Take Away'"></span>
                            </p>
                            <div class="border-t border-dashed border-gray-300 my-3"></div>

                            <div class="space-y-2 max-h-60 overflow-y-auto text-sm">
                                <template x-for="item in items" :key="item.product_id">
                                    <div>
                                        <div class="flex justify-between text-gray-800">
                                            <span x-text="item.name"></span>
                                            <span>RM <span x-text="(item.price * item.qty).toFixed(2)"></span></span>
                                        </div>
                                        <p class="text-xs text-gray-400" x-text="item.qty + ' x RM ' + item.price.toFixed(2)"></p>
                                    </div>
                                </template>
                            </div>

                            <div class="border-t border-dashed border-gray-300 my-3"></div>
                            <div class="text-sm space-y-1">
                                <div class="flex justify-between text-gray-700">
                                    <span>Subtotal</span>
                                    <span>RM <span x-text="subtotal().toFixed(2)"></span></span>
                                </div>
                                <div class="flex justify-between text-gray-700" x-show="discount > 0">
                                    <span>Diskaun</span>
                                    <span>RM <span x-text="(discount || 0).toFixed(2)"></span></span>
                                </div>
                                <div class="flex justify-between font-bold text-gray-900 text-base">
                                    <span>Jumlah</span>
                                    <span>RM <span x-text="total().toFixed(2)"></span></span>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500">
                                    <span>Bayaran</span>
                                    <span x-text="paymentMethod === 'cash' ? 'Cash' : 'QR / DuitNow'"></span>
                                </div>
                            </div>
                            <div class="border-t border-dashed border-gray-300 my-3"></div>
                            <p class="text-center text-xs text-gray-500">Terima kasih!</p>

                            <div class="mt-5 flex gap-2 font-sans">
                                <template x-if="! orderResult">
                                    <button type="button" @click="checkout()" :disabled="submitting"
                                        class="flex-1 bg-amber-500 hover:bg-amber-600 disabled:bg-gray-300 text-white font-semibold py-2 rounded-lg">
                                        <span x-text="submitting ? 'Menghantar...' : 'Print'"></span>
                                    </button>
                                </template>
                                <template x-if="! orderResult">
                                    <button type="button" @click="confirming = false" :disabled="submitting"
                                        class="flex-1 border border-gray-300 text-gray-600 font-medium py-2 rounded-lg hover:bg-gray-50 disabled:opacity-50">
                                        Back
                                    </button>
                                </template>
                                <template x-if="orderResult">
                                    <button type="button" @click="reprintFromPreview()"
                                        class="flex-1 border border-gray-300 text-gray-600 font-medium py-2 rounded-lg hover:bg-gray-50">
                                        Print
                                    </button>
                                </template>
                                <template x-if="orderResult">
                                    <button type="button" @click="resetAfterPrint()"
                                        class="flex-1 bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2 rounded-lg">
                                        Order Baru
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="bg-white shadow-sm rounded-lg mt-6"
                x-data="orderHistory(@js($todaysOrders->map(fn ($o) => [
                    'id' => $o->id,
                    'orderNumber' => $o->order_number,
                    'total' => (float) $o->total,
                    'date' => $o->created_at->format('d/m/Y'),
                    'time' => $o->created_at->format('H:i'),
                    'status' => $o->status,
                    'voidReason' => $o->void_reason,
                    'voidedAt' => $o->voided_at?->format('d/m/Y H:i'),
                    'receiptUrl' => route('orders.receipt', $o),
                ])))"
                @order-created.window="orders.unshift($event.detail)">
                <button type="button" @click="open = ! open" class="w-full flex items-center justify-between p-4 text-left">
                    <span class="text-sm font-semibold text-gray-500 uppercase">
                        Order Hari Ini
                        <span class="text-gray-400 normal-case font-normal">| {{ now()->translatedFormat('d F Y') }} | {{ now()->translatedFormat('l') }}</span>
                    </span>
                    <svg class="w-4 h-4 text-gray-400 transition-transform shrink-0" :class="open && 'rotate-180'"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="open" x-transition class="border-t border-gray-100 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs text-gray-500 uppercase">
                                <th class="px-4 py-2">No. Resit</th>
                                <th class="px-4 py-2 text-right">Harga</th>
                                <th class="px-4 py-2">Tarikh</th>
                                <th class="px-4 py-2">Masa</th>
                                <th class="px-4 py-2 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="order in orders" :key="order.id">
                                <tr>
                                    <td class="px-4 py-2" :class="order.status === 'voided' ? 'line-through text-gray-400' : 'text-gray-800'">
                                        <span x-text="order.orderNumber"></span>
                                        <p class="text-xs text-red-500 mt-0.5 no-underline" x-show="order.status === 'voided'"
                                            x-text="'Dibatalkan ' + order.voidedAt + ': ' + order.voidReason"></p>
                                    </td>
                                    <td class="px-4 py-2 text-right whitespace-nowrap" :class="order.status === 'voided' ? 'line-through text-gray-400' : 'text-gray-800'">
                                        RM <span x-text="order.total.toFixed(2)"></span>
                                    </td>
                                    <td class="px-4 py-2 text-gray-500 whitespace-nowrap" x-text="order.date"></td>
                                    <td class="px-4 py-2 text-gray-500 whitespace-nowrap" x-text="order.time"></td>
                                    <td class="px-4 py-2 text-right whitespace-nowrap">
                                        <a :href="order.receiptUrl" target="_blank" class="text-amber-600 hover:underline text-xs">Cetak Semula</a>
                                        <button type="button" x-show="order.status !== 'voided'" @click="voidOrder(order)"
                                            class="text-red-500 hover:underline text-xs ml-3">
                                            Void
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="orders.length === 0">
                                <td colspan="5" class="px-4 py-6 text-center text-gray-400 text-sm">Tiada order lagi hari ini.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        const POS_PENDING_KEY = 'sb_pending_orders';
        const PAPER_58MM = {{ $project->receipt_paper_width === '58mm' ? 'true' : 'false' }};
        const ORDERS_BASE_URL = '{{ url('/orders') }}';

        function orderHistory(initialOrders) {
            return {
                orders: initialOrders,
                open: false,

                async voidOrder(order) {
                    const reason = prompt('Sebab void order ' + order.orderNumber + ':');
                    if (reason === null) {
                        return;
                    }
                    if (reason.trim().length < 3) {
                        alert('Sila isi sebab (minimum 3 aksara).');
                        return;
                    }

                    try {
                        const res = await fetch(`${ORDERS_BASE_URL}/${order.id}/void`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ reason: reason.trim() }),
                        });

                        if (res.ok) {
                            const data = await res.json();
                            order.status = 'voided';
                            order.voidReason = reason.trim();
                            order.voidedAt = data.voided_at;
                            return;
                        }

                        const err = await res.json().catch(() => ({}));
                        alert(err.message || 'Gagal void order.');
                    } catch (e) {
                        alert('Gagal void order. Sila cuba lagi.');
                    }
                },
            };
        }

        function posCart(hasSession) {
            return {
                hasSession: hasSession,
                items: [],
                discount: 0,
                paymentMethod: 'qr',
                orderType: 'dine_in',
                pendingOrders: [],
                syncing: false,
                submitting: false,
                syncMessage: '',
                printerConnected: false,
                printerBusy: false,
                confirming: false,
                confirmDateStr: '',
                orderResult: null,

                openConfirm() {
                    if (! this.hasSession || this.items.length === 0 || this.submitting) {
                        return;
                    }
                    const now = new Date();
                    const pad = (n) => String(n).padStart(2, '0');
                    this.confirmDateStr = `${pad(now.getDate())}/${pad(now.getMonth() + 1)}/${now.getFullYear()} ${pad(now.getHours())}:${pad(now.getMinutes())}`;
                    this.orderResult = null;
                    this.confirming = true;
                },

                resetAfterPrint() {
                    this.confirming = false;
                    this.orderResult = null;
                    this.items = [];
                    this.discount = 0;
                },

                init() {
                    this.loadPending();
                    window.addEventListener('online', () => { this.trySync(); });
                    this.checkConnectivity();
                    setInterval(() => this.checkConnectivity(), 20000);

                    this.refreshPrinterStatus();
                    if (window.SBPrinter && window.SBPrinter.isSupported()) {
                        window.SBPrinter.ready.then(() => this.refreshPrinterStatus());
                    }
                },

                refreshPrinterStatus() {
                    this.printerConnected = !!(window.SBPrinter && window.SBPrinter.isConnected());
                },

                async connectPrinter() {
                    if (! (window.SBPrinter && window.SBPrinter.isSupported())) {
                        return;
                    }
                    this.printerBusy = true;
                    try {
                        await window.SBPrinter.connect();
                    } catch (e) {
                        console.error('Bluetooth connect gagal', e);
                    }
                    this.refreshPrinterStatus();
                    this.printerBusy = false;
                },

                loadPending() {
                    try {
                        this.pendingOrders = JSON.parse(localStorage.getItem(POS_PENDING_KEY) || '[]');
                    } catch (e) {
                        this.pendingOrders = [];
                    }
                },

                savePending() {
                    localStorage.setItem(POS_PENDING_KEY, JSON.stringify(this.pendingOrders));
                },

                async checkConnectivity() {
                    if (! navigator.onLine) {
                        this.$store.connectivity.online = false;
                        return;
                    }
                    try {
                        const res = await fetch('{{ route('pos.ping') }}', { cache: 'no-store' });
                        this.$store.connectivity.online = res.ok;
                    } catch (e) {
                        this.$store.connectivity.online = false;
                    }
                    if (this.$store.connectivity.online) {
                        this.trySync();
                    }
                },

                addItem(id, name, price) {
                    if (! this.hasSession) {
                        return;
                    }

                    const existing = this.items.find(i => i.product_id === id);
                    if (existing) {
                        existing.qty++;
                    } else {
                        this.items.push({ product_id: id, name: name, price: price, qty: 1 });
                    }
                },
                increment(index) {
                    this.items[index].qty++;
                },
                decrement(index) {
                    this.items[index].qty--;
                    if (this.items[index].qty <= 0) {
                        this.items.splice(index, 1);
                    }
                },
                subtotal() {
                    return this.items.reduce((sum, i) => sum + i.price * i.qty, 0);
                },
                total() {
                    return Math.max(this.subtotal() - (this.discount || 0), 0);
                },

                buildPayload() {
                    return {
                        items: this.items.map(i => ({ product_id: i.product_id, qty: i.qty })),
                        discount: this.discount || 0,
                        payment_method: this.paymentMethod,
                        order_type: this.orderType,
                    };
                },

                async checkout() {
                    if (! this.hasSession || this.items.length === 0 || this.submitting) {
                        return;
                    }

                    const payload = this.buildPayload();

                    if (! this.$store.connectivity.online) {
                        this.queueOffline(payload);
                        return;
                    }

                    this.submitting = true;

                    try {
                        const res = await fetch('{{ route('pos.store') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify(payload),
                        });

                        if (res.ok) {
                            const data = await res.json();
                            await this.printViaBluetooth(this.buildReceiptData(data.order_number, this.items, payload, new Date()));
                            this.orderResult = { orderNumber: data.order_number, receiptUrl: data.receipt_url };
                            this.submitting = false;

                            const now = new Date();
                            const pad = (n) => String(n).padStart(2, '0');
                            window.dispatchEvent(new CustomEvent('order-created', { detail: {
                                id: data.order_id,
                                orderNumber: data.order_number,
                                total: this.total(),
                                date: `${pad(now.getDate())}/${pad(now.getMonth() + 1)}/${now.getFullYear()}`,
                                time: `${pad(now.getHours())}:${pad(now.getMinutes())}`,
                                status: 'completed',
                                voidReason: null,
                                voidedAt: null,
                                receiptUrl: data.receipt_url,
                            } }));

                            return;
                        }

                        if (res.status === 419 || res.status === 401) {
                            alert('Sesi log masuk luput. Sila log masuk semula.');
                            this.submitting = false;
                            return;
                        }

                        const err = await res.json().catch(() => ({}));
                        alert(err.message || 'Order gagal dihantar.');
                        this.submitting = false;
                    } catch (e) {
                        this.$store.connectivity.online = false;
                        this.submitting = false;
                        this.queueOffline(payload);
                    }
                },

                async reprintFromPreview() {
                    if (! this.orderResult) {
                        return;
                    }
                    const payload = this.buildPayload();
                    const data = this.buildReceiptData(this.orderResult.orderNumber, this.items, payload, new Date());
                    const printed = await this.printViaBluetooth(data, { forceConnect: true });
                    if (! printed && window.SBPrinter && window.SBPrinter.isSupported()) {
                        alert('Printer tidak connect. Sila cuba lagi.');
                    }
                },

                queueOffline(payload) {
                    const pending = {
                        localId: 'local-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8),
                        items: this.items.map(i => ({ name: i.name, price: i.price, qty: i.qty })),
                        payload: payload,
                        createdAt: new Date().toISOString(),
                    };
                    this.pendingOrders.push(pending);
                    this.savePending();
                    this.printLocalReceipt(pending);
                    this.orderResult = { orderNumber: 'Menunggu Sync', receiptUrl: null, offline: true };
                },

                buildReceiptData(orderNumber, itemsList, payload, when) {
                    const subtotal = itemsList.reduce((s, i) => s + i.price * i.qty, 0);
                    const discount = payload.discount || 0;
                    const total = Math.max(subtotal - discount, 0);
                    const typeLabels = { dine_in: 'Dine In', takeaway: 'Take Away' };
                    const paymentLabels = { cash: 'Cash', qr: 'QR / DuitNow', card: 'Kad Debit/Kad Kredit' };
                    const pad = (n) => String(n).padStart(2, '0');

                    return {
                        orderNumber: orderNumber,
                        dateStr: `${pad(when.getDate())}/${pad(when.getMonth() + 1)}/${when.getFullYear()} ${pad(when.getHours())}:${pad(when.getMinutes())}`,
                        typeLabel: typeLabels[payload.order_type] || payload.order_type,
                        items: itemsList.map(i => ({
                            name: i.name,
                            qty: i.qty,
                            price: i.price.toFixed(2),
                            lineTotal: (i.price * i.qty).toFixed(2),
                        })),
                        subtotal: subtotal.toFixed(2),
                        discount: discount,
                        total: total.toFixed(2),
                        paymentLabel: paymentLabels[payload.payment_method] || payload.payment_method,
                    };
                },

                // Prints via the already-connected Bluetooth printer. Since checkout
                // stays on this same page (no navigation) until after printing, a
                // printer connected once at the start of a shift keeps working for
                // every order without needing to reselect it each time. forceConnect
                // re-requests the device (shows the OS picker) when silent reconnect
                // fails - only used for the offline/manual print path, not the quiet
                // auto-print after a normal checkout.
                async printViaBluetooth(data, { forceConnect = false } = {}) {
                    if (! (window.SBPrinter && window.SBPrinter.isSupported())) {
                        return false;
                    }

                    if (! window.SBPrinter.isConnected()) {
                        await window.SBPrinter.waitUntilReady(1500);
                    }

                    if (! window.SBPrinter.isConnected() && forceConnect) {
                        try {
                            await window.SBPrinter.connect();
                        } catch (e) {
                            console.error('Bluetooth connect gagal', e);
                        }
                    }

                    this.refreshPrinterStatus();

                    if (! window.SBPrinter.isConnected()) {
                        return false;
                    }

                    try {
                        const bytes = window.buildReceiptEscPos(data, PAPER_58MM);
                        await window.SBPrinter.write(bytes);
                        return true;
                    } catch (e) {
                        console.error('Bluetooth print gagal', e);
                        this.refreshPrinterStatus();
                        return false;
                    }
                },

                async printLocalReceipt(pending) {
                    const data = this.buildReceiptData('Menunggu Sync', pending.items, pending.payload, new Date(pending.createdAt));

                    const printed = await this.printViaBluetooth(data, { forceConnect: true });
                    if (printed) {
                        return;
                    }

                    if (window.SBPrinter && window.SBPrinter.isSupported()) {
                        alert('Printer tidak connect. Order tetap disimpan — sila sambung printer di Settings > Printer Resit (Bluetooth).');
                        return;
                    }

                    const win = window.open('', '_blank');
                    if (! win) {
                        return;
                    }

                    const esc = (s) => String(s).replace(/[&<>]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c]));
                    const itemsHtml = data.items.map(i => `
                        <tr>
                            <td>${esc(i.name)}<br><span class="muted">${i.qty} x RM ${i.price}</span></td>
                            <td class="right">RM ${i.lineTotal}</td>
                        </tr>
                    `).join('');

                    win.document.write(`
                        <!DOCTYPE html><html lang="ms"><head><meta charset="utf-8"><title>Resit (Menunggu Sync)</title>
                        <style>
                            * { box-sizing: border-box; }
                            body { font-family: 'Courier New', monospace; width: ${PAPER_58MM ? '220px' : '300px'}; margin: 0 auto; padding: ${PAPER_58MM ? '10px' : '16px'}; font-size: ${PAPER_58MM ? '11px' : '13px'}; color: #111; }
                            h1 { font-size: ${PAPER_58MM ? '14px' : '16px'}; text-align: center; margin: 0 0 4px; }
                            .center { text-align: center; }
                            .muted { color: #555; font-size: ${PAPER_58MM ? '9px' : '11px'}; }
                            .divider { border-top: 1px dashed #999; margin: ${PAPER_58MM ? '6px' : '8px'} 0; }
                            table { width: 100%; border-collapse: collapse; }
                            td { padding: 2px 0; vertical-align: top; }
                            .right { text-align: right; white-space: nowrap; }
                            .grand { font-weight: bold; font-size: ${PAPER_58MM ? '12px' : '14px'}; }
                            .pending-badge { text-align: center; background: #fef3c7; color: #92400e; padding: 6px; font-size: 11px; margin-bottom: 8px; border-radius: 4px; }
                        </style></head><body>
                            <h1>SAJIAN BAGINDA</h1>
                            <p class="center muted">Warisan Rasa Pantai Timur</p>
                            <div class="pending-badge">MENUNGGU SYNC — akan dapat No. Resit rasmi bila online</div>
                            <div class="divider"></div>
                            <p class="muted">
                                No. Resit: Menunggu Sync<br>
                                Tarikh: ${data.dateStr}<br>
                                Jenis: ${data.typeLabel}
                            </p>
                            <div class="divider"></div>
                            <table>${itemsHtml}</table>
                            <div class="divider"></div>
                            <table>
                                <tr><td>Subtotal</td><td class="right">RM ${data.subtotal}</td></tr>
                                ${data.discount > 0 ? `<tr><td>Diskaun</td><td class="right">RM ${data.discount.toFixed(2)}</td></tr>` : ''}
                                <tr class="grand"><td>Jumlah</td><td class="right">RM ${data.total}</td></tr>
                                <tr><td class="muted">Bayaran</td><td class="right muted">${data.paymentLabel}</td></tr>
                            </table>
                            <div class="divider"></div>
                            <p class="center muted">Terima kasih!</p>
                            <script>window.print();<\/script>
                        </body></html>
                    `);
                    win.document.close();
                },

                async trySync() {
                    if (this.syncing || this.pendingOrders.length === 0 || ! this.$store.connectivity.online) {
                        return;
                    }

                    this.syncing = true;
                    let syncedCount = 0;

                    while (this.pendingOrders.length > 0) {
                        const next = this.pendingOrders[0];

                        try {
                            const res = await fetch('{{ route('pos.store') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: JSON.stringify(next.payload),
                            });

                            if (res.ok) {
                                this.pendingOrders.shift();
                                this.savePending();
                                syncedCount++;
                                continue;
                            }

                            if (res.status === 419 || res.status === 401) {
                                this.syncMessage = 'Sesi log masuk luput — sila log masuk semula untuk sync order tertunda.';
                                break;
                            }

                            this.syncMessage = 'Ada order tertunda gagal disync. Hubungi admin.';
                            break;
                        } catch (e) {
                            this.$store.connectivity.online = false;
                            break;
                        }
                    }

                    this.syncing = false;

                    if (syncedCount > 0) {
                        this.syncMessage = syncedCount + ' order berjaya disync.';
                        setTimeout(() => { this.syncMessage = ''; }, 6000);
                    }
                },
            };
        }
    </script>
</x-app-layout>
