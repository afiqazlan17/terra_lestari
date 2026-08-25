<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Menu &amp; Kategori</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase mb-4">Tambah Kategori</h3>
                <form method="POST" action="{{ route('products.categories.store') }}" class="flex items-end gap-3">
                    @csrf
                    <div class="flex-1">
                        <x-input-label for="category_name" value="Nama Kategori (contoh: Nasi, Minuman)" />
                        <x-text-input id="category_name" name="name" type="text" class="mt-1 block w-full" required />
                    </div>
                    <x-secondary-button type="submit">Tambah</x-secondary-button>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase mb-4">Tambah Item Menu</h3>
                <form method="POST" action="{{ route('products.store') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                    @csrf
                    <div class="sm:col-span-2">
                        <x-input-label for="product_name" value="Nama Item" />
                        <x-text-input id="product_name" name="name" type="text" class="mt-1 block w-full" required />
                    </div>
                    <div>
                        <x-input-label for="category_id" value="Kategori" />
                        <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="">- Tiada -</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="price" value="Harga (RM)" />
                        <x-text-input id="price" name="price" type="number" step="0.01" min="0" class="mt-1 block w-full" required />
                    </div>
                    <div class="sm:col-span-4">
                        <x-input-error :messages="$errors->all()" class="mt-1" />
                        <x-primary-button type="submit">Tambah Menu</x-primary-button>
                    </div>
                </form>
            </div>

            @foreach ($categories as $category)
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-3 bg-gray-50 text-sm font-semibold text-gray-600">{{ $category->name }}</div>
                    @if ($category->products->isEmpty())
                        <p class="p-6 text-sm text-gray-400">Belum ada item.</p>
                    @else
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($category->products as $product)
                                    <tr>
                                        <td class="px-6 py-3 text-gray-800">{{ $product->name }}</td>
                                        <td class="px-6 py-3 text-gray-600">RM {{ number_format($product->price, 2) }}</td>
                                        <td class="px-6 py-3">
                                            <span class="inline-flex rounded-full px-2 py-1 text-xs {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                                {{ $product->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3 text-right space-x-3">
                                            <form method="POST" action="{{ route('products.update', $product) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="name" value="{{ $product->name }}">
                                                <input type="hidden" name="price" value="{{ $product->price }}">
                                                <input type="hidden" name="is_active" value="{{ $product->is_active ? '0' : '1' }}">
                                                <button type="submit" class="text-amber-600 hover:underline text-xs">
                                                    {{ $product->is_active ? 'Nyahaktifkan' : 'Aktifkan' }}
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('products.destroy', $product) }}" class="inline" onsubmit="return confirm('Padam menu ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:underline text-xs">Padam</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @endforeach

            @if ($categories->isEmpty())
                <p class="text-center text-gray-400">Tambah kategori dahulu untuk mula tambah menu.</p>
            @endif
        </div>
    </div>
</x-app-layout>
