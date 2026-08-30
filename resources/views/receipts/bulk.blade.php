<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Upload Pukal Resit</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-600 mb-3">
                    Pilih beberapa gambar/PDF resit sekali gus (boleh campur Belian dan Perbelanjaan) - setiap satu akan
                    dibaca automatik, kategori akan ditekakan, dan kau boleh semak/betulkan sebelum simpan semua.
                </p>
                <input id="bulk-file-input" type="file" accept="image/*,.pdf,application/pdf" multiple
                    class="block w-full text-sm text-gray-600" />
                <p id="bulk-summary" class="mt-2 text-xs text-gray-400"></p>
            </div>

            <div id="bulk-table-wrap" class="hidden bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs text-gray-500 uppercase">
                                <th class="px-3 py-3">Resit</th>
                                <th class="px-3 py-3">Kategori</th>
                                <th class="px-3 py-3">Tarikh</th>
                                <th class="px-3 py-3">Keterangan</th>
                                <th class="px-3 py-3">Pembekal</th>
                                <th class="px-3 py-3">Jumlah (RM)</th>
                                <th class="px-3 py-3"></th>
                            </tr>
                        </thead>
                        <tbody id="bulk-tbody" class="divide-y divide-gray-100"></tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-gray-100 flex items-center gap-3">
                    <button type="button" id="bulk-save-btn" onclick="saveAll()"
                        class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-4 py-2 rounded-lg disabled:opacity-50">
                        Simpan Semua
                    </button>
                    <p id="bulk-save-status" class="text-xs text-gray-500"></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const EXTRACT_URL = '{{ route('receipts.extract') }}';
        const BULK_STORE_URL = '{{ route('receipts.bulk.store') }}';
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
        const CATEGORIES = @json(\App\Models\Purchase::CATEGORIES);

        let rows = [];

        document.getElementById('bulk-file-input').addEventListener('change', (e) => {
            handleFiles(e.target.files);
            e.target.value = '';
        });

        async function handleFiles(fileList) {
            document.getElementById('bulk-table-wrap').classList.remove('hidden');

            for (const file of Array.from(fileList)) {
                const row = {
                    file,
                    category: 'bahan_mentah',
                    purchase_date: '',
                    description: '',
                    supplier_name: '',
                    amount: '',
                    notes: '',
                    status: 'loading',
                    duplicate: null,
                    error: null,
                };
                rows.push(row);
                renderTable();
                await extractRow(row);
                renderTable();
            }
        }

        async function extractRow(row) {
            const formData = new FormData();
            formData.append('receipt', row.file);

            try {
                const res = await fetch(EXTRACT_URL, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                    body: formData,
                });
                const data = await res.json();

                if (!res.ok) {
                    row.status = 'error';
                    row.error = data.error || 'Gagal baca resit';
                    return;
                }

                row.category = data.category && CATEGORIES[data.category] ? data.category : 'bahan_mentah';
                row.purchase_date = data.purchase_date || '';
                row.description = data.description || '';
                row.supplier_name = data.supplier_name || '';
                row.amount = data.amount || '';
                row.duplicate = data.duplicate || null;
                row.status = 'done';
            } catch (err) {
                row.status = 'error';
                row.error = 'Gagal baca resit';
            }
        }

        function removeRow(index) {
            rows.splice(index, 1);
            renderTable();
        }

        function fieldInput(type, value, onInput, extraClass = '') {
            const input = document.createElement(type === 'textarea' ? 'textarea' : 'input');
            if (type !== 'textarea') input.type = type;
            input.value = value ?? '';
            input.className = 'rounded-md border-gray-300 shadow-sm text-xs w-full ' + extraClass;
            input.addEventListener('input', (e) => onInput(e.target.value));
            return input;
        }

        function renderTable() {
            const tbody = document.getElementById('bulk-tbody');
            tbody.innerHTML = '';

            rows.forEach((row, i) => {
                const tr = document.createElement('tr');

                // Resit column
                const tdFile = document.createElement('td');
                tdFile.className = 'px-3 py-2 align-top';
                const fileName = document.createElement('p');
                fileName.className = 'text-xs text-gray-500 max-w-[10rem] truncate';
                fileName.textContent = row.file.name;
                tdFile.appendChild(fileName);
                if (row.status === 'loading') {
                    const loading = document.createElement('p');
                    loading.className = 'text-xs text-amber-600';
                    loading.textContent = 'Membaca...';
                    tdFile.appendChild(loading);
                } else if (row.status === 'error') {
                    const errEl = document.createElement('p');
                    errEl.className = 'text-xs text-red-500';
                    errEl.textContent = row.error;
                    tdFile.appendChild(errEl);
                }
                if (row.duplicate) {
                    const dup = document.createElement('p');
                    dup.className = 'text-xs text-amber-700 bg-amber-50 rounded px-1.5 py-0.5 mt-1';
                    dup.textContent = 'Mungkin duplicate: "' + row.duplicate.description + '" oleh '
                        + (row.duplicate.recorded_by || '-') + ', ' + row.duplicate.recorded_at;
                    tdFile.appendChild(dup);
                }
                tr.appendChild(tdFile);

                // Kategori
                const tdCategory = document.createElement('td');
                tdCategory.className = 'px-3 py-2 align-top';
                const select = document.createElement('select');
                select.className = 'rounded-md border-gray-300 shadow-sm text-xs w-full';
                Object.entries(CATEGORIES).forEach(([value, label]) => {
                    const opt = document.createElement('option');
                    opt.value = value;
                    opt.textContent = label;
                    if (value === row.category) opt.selected = true;
                    select.appendChild(opt);
                });
                select.addEventListener('change', (e) => { row.category = e.target.value; });
                tdCategory.appendChild(select);
                tr.appendChild(tdCategory);

                // Tarikh
                const tdDate = document.createElement('td');
                tdDate.className = 'px-3 py-2 align-top';
                tdDate.appendChild(fieldInput('date', row.purchase_date, (v) => { row.purchase_date = v; }));
                tr.appendChild(tdDate);

                // Keterangan
                const tdDesc = document.createElement('td');
                tdDesc.className = 'px-3 py-2 align-top';
                tdDesc.appendChild(fieldInput('text', row.description, (v) => { row.description = v; }, 'min-w-[8rem]'));
                tr.appendChild(tdDesc);

                // Pembekal
                const tdSupplier = document.createElement('td');
                tdSupplier.className = 'px-3 py-2 align-top';
                tdSupplier.appendChild(fieldInput('text', row.supplier_name, (v) => { row.supplier_name = v; }, 'min-w-[8rem]'));
                tr.appendChild(tdSupplier);

                // Jumlah
                const tdAmount = document.createElement('td');
                tdAmount.className = 'px-3 py-2 align-top';
                tdAmount.appendChild(fieldInput('number', row.amount, (v) => { row.amount = v; }, 'w-24'));
                tr.appendChild(tdAmount);

                // Buang
                const tdRemove = document.createElement('td');
                tdRemove.className = 'px-3 py-2 align-top text-right';
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'text-red-500 hover:underline text-xs';
                removeBtn.textContent = 'Buang';
                removeBtn.addEventListener('click', () => removeRow(i));
                tdRemove.appendChild(removeBtn);
                tr.appendChild(tdRemove);

                tbody.appendChild(tr);
            });

            document.getElementById('bulk-summary').textContent = rows.length
                ? rows.length + ' resit dipilih.'
                : '';
        }

        async function saveAll() {
            const readyRows = rows.filter(r => r.status !== 'loading');

            if (readyRows.length === 0) {
                return;
            }

            const btn = document.getElementById('bulk-save-btn');
            const status = document.getElementById('bulk-save-status');
            btn.disabled = true;
            status.textContent = 'Menyimpan...';

            const formData = new FormData();
            readyRows.forEach((row, i) => {
                formData.append(`receipts[${i}][category]`, row.category);
                formData.append(`receipts[${i}][purchase_date]`, row.purchase_date);
                formData.append(`receipts[${i}][description]`, row.description);
                formData.append(`receipts[${i}][supplier_name]`, row.supplier_name || '');
                formData.append(`receipts[${i}][amount]`, row.amount);
                formData.append(`receipts[${i}][notes]`, row.notes || '');
                formData.append(`receipts[${i}][file]`, row.file);
            });

            try {
                const res = await fetch(BULK_STORE_URL, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                    body: formData,
                });
                const data = await res.json();

                if (!res.ok) {
                    status.textContent = 'Gagal simpan. Sila cuba lagi.';
                    btn.disabled = false;
                    return;
                }

                const failedIndexes = new Set((data.errors || []).map(e => e.index));
                const stillFailing = readyRows.filter((_, i) => failedIndexes.has(i));

                rows = rows.filter(r => r.status === 'loading' || stillFailing.includes(r));
                (data.errors || []).forEach((err) => {
                    const row = readyRows[err.index];
                    if (row) row.error = err.message;
                });

                status.textContent = data.created + ' rekod berjaya disimpan.'
                    + (stillFailing.length ? ' ' + stillFailing.length + ' gagal, sila semak dan cuba lagi.' : '');

                renderTable();
            } catch (err) {
                status.textContent = 'Gagal simpan. Sila cuba lagi.';
            }

            btn.disabled = false;
        }
    </script>
</x-app-layout>
