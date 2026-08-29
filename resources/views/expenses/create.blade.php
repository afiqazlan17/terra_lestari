<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Rekod Perbelanjaan</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="purchase_date" value="Tarikh" />
                        <x-text-input id="purchase_date" name="purchase_date" type="date" class="mt-1 block w-full"
                            :value="old('purchase_date', now()->toDateString())" required />
                        <x-input-error :messages="$errors->get('purchase_date')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="category" value="Kategori" />
                        <select id="category" name="category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" required>
                            @foreach (\App\Models\Purchase::EXPENSE_CATEGORIES as $value => $label)
                                <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('category')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="description" value="Keterangan (contoh: Gaji Bulan Ogos, Bil Elektrik, Renovasi Dapur)" />
                        <x-text-input id="description" name="description" type="text" class="mt-1 block w-full"
                            :value="old('description')" required autofocus />
                        <x-input-error :messages="$errors->get('description')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="supplier_name" value="Pembekal/Penerima (Jika ada)" />
                        <x-text-input id="supplier_name" name="supplier_name" type="text" class="mt-1 block w-full"
                            :value="old('supplier_name')" />
                        <x-input-error :messages="$errors->get('supplier_name')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="amount" value="Jumlah (RM)" />
                        <x-text-input id="amount" name="amount" type="text" inputmode="decimal" data-money-input class="mt-1 block w-full"
                            :value="old('amount')" required />
                        <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="receipt" value="Gambar/PDF Resit (Jika ada)" />
                        <input id="receipt" name="receipt" type="file" accept="image/*,.pdf,application/pdf"
                            class="mt-1 block w-full text-sm text-gray-600" onchange="autoFillFromReceipt(this)" />
                        <p id="receipt-status" class="mt-1 text-xs text-gray-400"></p>
                        <x-input-error :messages="$errors->get('receipt')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="notes" value="Nota (Jika ada)" />
                        <textarea id="notes" name="notes" rows="2"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old('notes') }}</textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <x-primary-button type="submit">Simpan Perbelanjaan</x-primary-button>
                        <a href="{{ route('expenses.index') }}" class="text-sm text-gray-500 hover:underline">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        async function autoFillFromReceipt(input) {
            const file = input.files[0];
            const status = document.getElementById('receipt-status');
            if (!file) {
                status.textContent = '';
                return;
            }

            status.textContent = 'Membaca resit...';

            const formData = new FormData();
            formData.append('receipt', file);

            try {
                const res = await fetch('{{ route('receipts.extract') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                const data = await res.json();

                if (!res.ok) {
                    status.textContent = data.error || 'Gagal baca resit. Sila isi manual.';
                    return;
                }

                if (data.purchase_date) document.getElementById('purchase_date').value = data.purchase_date;
                if (data.amount) document.getElementById('amount').value = data.amount;
                if (data.supplier_name) document.getElementById('supplier_name').value = data.supplier_name;
                if (data.description) document.getElementById('description').value = data.description;

                status.textContent = 'Borang diisi automatik - sila semak sebelum simpan (termasuk Kategori).';
            } catch (err) {
                status.textContent = 'Gagal baca resit. Sila isi manual.';
            }
        }
    </script>
</x-app-layout>
