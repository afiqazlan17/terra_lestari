<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Katalog Produk NBK</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('nbk.orders.create') }}" class="border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-semibold px-4 py-2 rounded-lg">
                    Buat Order
                </a>
                <a href="{{ route('nbk.orders.index') }}" class="border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-semibold px-4 py-2 rounded-lg">
                    Sejarah Order
                </a>
                <a href="{{ route('nbk.index') }}" class="text-gray-500 hover:text-gray-700 text-sm px-2">
                    &larr; Kembali
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase mb-4">Tambah Produk NBK</h3>
                <form method="POST" action="{{ route('nbk.products.store') }}" class="grid grid-cols-1 sm:grid-cols-5 gap-4 items-end">
                    @csrf
                    <div class="sm:col-span-2">
                        <x-input-label for="name" value="Nama Produk" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required />
                    </div>
                    <div>
                        <x-input-label for="unit_cost" value="Harga Beli / Unit (RM)" />
                        <x-text-input id="unit_cost" name="unit_cost" type="text" inputmode="decimal" data-money-input class="mt-1 block w-full" required />
                    </div>
                    <div>
                        <x-input-label for="min_qty" value="Kuantiti Minimum" />
                        <x-text-input id="min_qty" name="min_qty" type="number" step="1" min="1" value="1" class="mt-1 block w-full" required />
                    </div>
                    <div>
                        <x-input-label for="sell_price" value="Harga Jual / Unit (RM)" />
                        <x-text-input id="sell_price" name="sell_price" type="text" inputmode="decimal" data-money-input class="mt-1 block w-full" />
                    </div>
                    <div class="sm:col-span-5">
                        <x-input-error :messages="$errors->all()" class="mt-1" />
                        <x-primary-button type="submit">Tambah Produk</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                @if ($products->isEmpty())
                    <p class="p-6 text-sm text-gray-400">Belum ada produk NBK.</p>
                @else
                    <div class="overflow-x-auto" x-data="{ editingId: null }">
                        <table class="min-w-full table-fixed divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs text-gray-500 uppercase">
                                    <th class="px-4 py-2">Nama</th>
                                    <th class="px-4 py-2 text-right w-28">Harga Beli</th>
                                    <th class="px-4 py-2 text-right w-24">Min. Qty</th>
                                    <th class="px-4 py-2 text-right w-28">Harga Jual</th>
                                    <th class="px-4 py-2 w-24">Status</th>
                                    <th class="px-4 py-2 w-16"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($products as $product)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-800 truncate">{{ $product->name }}</td>
                                        <td class="px-4 py-3 text-gray-600 text-right tabular-nums whitespace-nowrap">RM {{ number_format($product->unit_cost, 2) }}</td>
                                        <td class="px-4 py-3 text-gray-600 text-right tabular-nums whitespace-nowrap">{{ $product->min_qty }}</td>
                                        <td class="px-4 py-3 text-right tabular-nums whitespace-nowrap">
                                            @if ($product->sell_price !== null)
                                                <span class="text-gray-600">RM {{ number_format($product->sell_price, 2) }}</span>
                                            @else
                                                <span class="text-xs text-amber-600">Belum diisi</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @php
                                                $statusColors = [
                                                    \App\Models\NbkProduct::STATUS_ACTIVE => 'bg-green-100 text-green-800',
                                                    \App\Models\NbkProduct::STATUS_OUT_OF_STOCK => 'bg-red-100 text-red-700',
                                                    \App\Models\NbkProduct::STATUS_EXCLUDED => 'bg-gray-200 text-gray-600',
                                                ];
                                            @endphp
                                            <span class="inline-flex rounded-full px-2 py-1 text-xs {{ $statusColors[$product->status] ?? 'bg-gray-200 text-gray-600' }}">
                                                {{ $product->statusLabel() }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <button type="button" @click="editingId = editingId === {{ $product->id }} ? null : {{ $product->id }}" class="text-amber-600 hover:underline text-xs">
                                                Edit
                                            </button>
                                        </td>
                                    </tr>
                                    <tr x-show="editingId === {{ $product->id }}" x-cloak>
                                        <td colspan="6" class="px-4 py-3 bg-gray-50">
                                            <form method="POST" action="{{ route('nbk.products.update', $product) }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                                                @csrf
                                                @method('PATCH')
                                                <div class="sm:col-span-2">
                                                    <label class="block text-xs text-gray-500 mb-1">Nama</label>
                                                    <input type="text" name="name" value="{{ $product->name }}" required class="rounded-md border-gray-300 shadow-sm text-sm w-full">
                                                </div>
                                                <div>
                                                    <label class="block text-xs text-gray-500 mb-1">Harga Beli (RM)</label>
                                                    <input type="text" inputmode="decimal" data-money-input name="unit_cost" value="{{ number_format($product->unit_cost, 2, '.', '') }}" required class="rounded-md border-gray-300 shadow-sm text-sm w-full">
                                                </div>
                                                <div>
                                                    <label class="block text-xs text-gray-500 mb-1">Kuantiti Minimum</label>
                                                    <input type="number" step="1" min="1" name="min_qty" value="{{ $product->min_qty }}" required class="rounded-md border-gray-300 shadow-sm text-sm w-full">
                                                </div>
                                                <div>
                                                    <label class="block text-xs text-gray-500 mb-1">Harga Jual (RM)</label>
                                                    <input type="text" inputmode="decimal" data-money-input name="sell_price" value="{{ $product->sell_price !== null ? number_format($product->sell_price, 2, '.', '') : '' }}" class="rounded-md border-gray-300 shadow-sm text-sm w-full">
                                                </div>
                                                <div class="sm:col-span-5 flex items-center justify-between pt-1">
                                                    <div class="flex items-center gap-2">
                                                        <label class="text-xs text-gray-500">Status</label>
                                                        <select name="status" class="rounded-md border-gray-300 shadow-sm text-sm">
                                                            @foreach (\App\Models\NbkProduct::STATUSES as $value => $label)
                                                                <option value="{{ $value }}" @selected($product->status === $value)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="flex items-center gap-3">
                                                        <button type="button" @click="editingId = null" class="text-xs text-gray-500 hover:underline">Batal</button>
                                                        <x-primary-button type="submit" class="!py-1.5 !px-3 text-xs">Simpan</x-primary-button>
                                                    </div>
                                                </div>
                                            </form>
                                            <form method="POST" action="{{ route('nbk.products.destroy', $product) }}" class="mt-2" onsubmit="return confirm('Padam produk ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:underline text-xs">Padam produk ini</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
