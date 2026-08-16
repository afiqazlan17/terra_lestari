<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            POS <span class="text-gray-400 font-normal">{{ $project->name }}</span>
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (! $session)
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center">
                    <p class="text-lg font-medium text-gray-800">Hari ni belum dibuka</p>
                    <p class="mt-1 text-sm text-gray-500 mb-4">Buka hari dulu sebelum boleh mula jual.</p>
                    <form method="POST" action="{{ route('daily-session.open') }}" class="inline-flex items-end gap-2">
                        @csrf
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Modal awal (RM)</label>
                            <input type="number" step="0.01" min="0" name="opening_cash" required
                                class="rounded-md border-gray-300 shadow-sm text-sm w-32">
                        </div>
                        <x-primary-button type="submit">Buka Hari</x-primary-button>
                    </form>
                </div>
            @else
                <div x-data="posCart()" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    {{-- Menu grid --}}
                    <div class="lg:col-span-2 space-y-6">
                        @forelse ($categories as $category)
                            @if ($category->products->isNotEmpty())
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-500 uppercase mb-2">{{ $category->name }}</h3>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                        @foreach ($category->products as $product)
                                            <button type="button"
                                                @click="addItem({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->price }})"
                                                class="bg-white shadow-sm rounded-lg p-4 text-left hover:ring-2 hover:ring-amber-400 active:scale-95 transition">
                                                <p class="font-medium text-gray-800">{{ $product->name }}</p>
                                                <p class="text-sm text-gray-500 mt-1">RM {{ number_format($product->price, 2) }}</p>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @empty
                            <div class="bg-white shadow-sm rounded-lg p-8 text-center text-gray-500">
                                Belum ada menu. Owner boleh tambah menu kat halaman <a href="{{ route('products.index') }}" class="text-amber-600 hover:underline">Menu</a>.
                            </div>
                        @endforelse
                    </div>

                    {{-- Cart --}}
                    <div class="bg-white shadow-sm rounded-lg p-4 h-fit sticky top-6">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase mb-3">Order</h3>

                        <template x-if="items.length === 0">
                            <p class="text-sm text-gray-400 py-6 text-center">Tap menu untuk tambah item</p>
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
                                <input type="number" step="0.01" min="0" x-model.number="discount" class="w-20 text-right rounded-md border-gray-300 text-sm">
                            </div>
                            <div class="flex justify-between font-semibold text-gray-900 text-base pt-1">
                                <span>Jumlah</span>
                                <span>RM <span x-text="total().toFixed(2)"></span></span>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-xs text-gray-500 mb-1">Cara Bayar</label>
                            <select x-model="paymentMethod" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                                <option value="cash">Tunai</option>
                                <option value="qr">QR / DuitNow</option>
                                <option value="card">Kad</option>
                            </select>
                        </div>

                        <form method="POST" :action="'{{ route('pos.store') }}'" @submit="return items.length > 0">
                            @csrf
                            <template x-for="(item, index) in items" :key="'field-' + item.product_id">
                                <div>
                                    <input type="hidden" :name="'items[' + index + '][product_id]'" :value="item.product_id">
                                    <input type="hidden" :name="'items[' + index + '][qty]'" :value="item.qty">
                                </div>
                            </template>
                            <input type="hidden" name="discount" :value="discount">
                            <input type="hidden" name="payment_method" :value="paymentMethod">

                            <button type="submit" :disabled="items.length === 0"
                                class="mt-4 w-full bg-amber-500 hover:bg-amber-600 disabled:bg-gray-300 text-white font-semibold py-3 rounded-lg transition">
                                Checkout
                            </button>
                        </form>
                        <button type="button" @click="items = []; discount = 0" x-show="items.length > 0"
                            class="mt-2 w-full text-sm text-gray-500 hover:text-red-600">
                            Kosongkan order
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        function posCart() {
            return {
                items: [],
                discount: 0,
                paymentMethod: 'cash',
                addItem(id, name, price) {
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
            };
        }
    </script>
</x-app-layout>
