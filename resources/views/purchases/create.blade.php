<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Rekod Belian Barang</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('purchases.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="purchase_date" value="Tarikh Belian" />
                        <x-text-input id="purchase_date" name="purchase_date" type="date" class="mt-1 block w-full"
                            :value="old('purchase_date', now()->toDateString())" required />
                        <x-input-error :messages="$errors->get('purchase_date')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="description" value="Keterangan (contoh: Ikan, Sayur, Beras)" />
                        <x-text-input id="description" name="description" type="text" class="mt-1 block w-full"
                            :value="old('description')" required autofocus />
                        <x-input-error :messages="$errors->get('description')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="supplier_name" value="Pembekal (jika ada)" />
                        <x-text-input id="supplier_name" name="supplier_name" type="text" class="mt-1 block w-full"
                            :value="old('supplier_name')" />
                        <x-input-error :messages="$errors->get('supplier_name')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="amount" value="Jumlah (RM)" />
                        <x-text-input id="amount" name="amount" type="number" step="0.01" min="0" class="mt-1 block w-full"
                            :value="old('amount')" required />
                        <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="receipt" value="Gambar Resit" />
                        <input id="receipt" name="receipt" type="file" accept="image/*" capture="environment"
                            class="mt-1 block w-full text-sm text-gray-600" />
                        <x-input-error :messages="$errors->get('receipt')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="notes" value="Nota (opsyenal)" />
                        <textarea id="notes" name="notes" rows="2"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old('notes') }}</textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <x-primary-button type="submit">Simpan Belian</x-primary-button>
                        <a href="{{ route('purchases.index') }}" class="text-sm text-gray-500 hover:underline">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
