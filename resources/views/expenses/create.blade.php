<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Rekod Perbelanjaan</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data"
                    class="space-y-6 sm:space-y-0 sm:grid sm:grid-cols-2 sm:gap-8 sm:items-start">
                    @csrf

                    <div>
                        <x-input-label for="receipt" value="1. Snap/Upload Resit Dulu (Jika ada)" />
                        <p class="text-xs text-gray-400 mb-1">Borang di sebelah akan diisi automatik selepas resit dibaca.</p>
                        <input id="receipt" name="receipt" type="file" accept="image/*,.pdf,application/pdf"
                            class="mt-1 block w-full text-sm text-gray-600" onchange="autoFillFromReceipt(this)" />
                        <p id="receipt-status" class="mt-1 text-xs text-gray-400"></p>
                        <x-input-error :messages="$errors->get('receipt')" class="mt-1" />

                        <div id="receipt-preview-wrap" class="hidden mt-3">
                            <img id="receipt-preview-image" class="hidden w-full rounded-lg border border-gray-200 max-h-[60vh] object-contain" />
                            <a id="receipt-preview-pdf-link" href="#" target="_blank" class="hidden inline-block text-sm text-amber-600 hover:underline border border-gray-200 rounded-lg px-3 py-2">
                                Buka fail PDF resit dalam tab baru untuk rujuk
                            </a>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <p class="text-xs text-gray-400 sm:-mt-1">2. Semak/Betulkan Butiran</p>

                        <div id="duplicate-warning" class="hidden bg-amber-50 border border-amber-200 text-amber-800 text-xs rounded-lg p-3"></div>

                        <div>
                            <x-input-label for="purchase_date" value="Tarikh" />
                            <x-text-input id="purchase_date" name="purchase_date" type="date" class="mt-1 block w-full"
                                :value="old('purchase_date', now()->toDateString())" required />
                            <x-input-error :messages="$errors->get('purchase_date')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="category" value="Kategori" />
                            <select id="category" name="category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" required>
                                <option value="">- Pilih -</option>
                                @foreach (\App\Models\Purchase::EXPENSE_CATEGORIES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('category')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="description" value="Keterangan (contoh: Gaji Bulan Ogos, Bil Elektrik, Renovasi Dapur)" />
                            <x-text-input id="description" name="description" type="text" class="mt-1 block w-full"
                                :value="old('description')" required />
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
                            <x-input-label for="notes" value="Nota (Jika ada)" />
                            <textarea id="notes" name="notes" rows="2"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old('notes') }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <x-primary-button type="submit">Simpan Perbelanjaan</x-primary-button>
                            <a href="{{ route('expenses.index') }}" class="text-sm text-gray-500 hover:underline">Batal</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showReceiptPreview(file) {
            const wrap = document.getElementById('receipt-preview-wrap');
            const img = document.getElementById('receipt-preview-image');
            const pdfLink = document.getElementById('receipt-preview-pdf-link');

            if (!file) {
                wrap.classList.add('hidden');
                img.classList.add('hidden');
                pdfLink.classList.add('hidden');
                return;
            }

            const url = URL.createObjectURL(file);
            wrap.classList.remove('hidden');

            if (file.type === 'application/pdf') {
                img.classList.add('hidden');
                pdfLink.href = url;
                pdfLink.classList.remove('hidden');
            } else {
                pdfLink.classList.add('hidden');
                img.src = url;
                img.classList.remove('hidden');
            }
        }

        function showDuplicateWarning(duplicate) {
            const el = document.getElementById('duplicate-warning');

            if (!duplicate) {
                el.classList.add('hidden');
                el.textContent = '';
                return;
            }

            el.textContent = 'Resit ni nampak macam dah pernah direkod: "' + duplicate.description + '"'
                + (duplicate.supplier_name ? ' (' + duplicate.supplier_name + ')' : '')
                + ', oleh ' + (duplicate.recorded_by || '-') + ' pada ' + duplicate.recorded_at
                + '. Kalau ni memang perbelanjaan berasingan, teruskan simpan seperti biasa.';
            el.classList.remove('hidden');
        }

        async function autoFillFromReceipt(input) {
            const file = input.files[0];
            const status = document.getElementById('receipt-status');

            showReceiptPreview(file);
            showDuplicateWarning(null);

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
                if (data.category) {
                    const categorySelect = document.getElementById('category');
                    if ([...categorySelect.options].some(opt => opt.value === data.category)) {
                        categorySelect.value = data.category;
                    }
                }
                showDuplicateWarning(data.duplicate);

                status.textContent = 'Borang diisi automatik - sila semak sebelum simpan.';
            } catch (err) {
                status.textContent = 'Gagal baca resit. Sila isi manual.';
            }
        }
    </script>
</x-app-layout>
