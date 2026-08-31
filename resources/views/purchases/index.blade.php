<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Belian Barang</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('receipts.bulk.create') }}" class="border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-semibold px-4 py-2 rounded-lg">
                    Upload Pukal
                </a>
                <a href="{{ route('purchases.create') }}" class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                    + Rekod Belian
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                @if ($purchases->isEmpty())
                    <p class="p-8 text-center text-gray-400">Belum ada belian direkodkan.</p>
                @else
                    <div class="overflow-x-auto" x-data="{ manageOpenId: null }">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs text-gray-500 uppercase">
                                    <th class="px-4 py-3 whitespace-nowrap">Tarikh</th>
                                    <th class="px-4 py-3">Keterangan</th>
                                    <th class="px-4 py-3 whitespace-nowrap">Pembekal</th>
                                    <th class="px-4 py-3 text-right whitespace-nowrap">Jumlah</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($purchases as $purchase)
                                    <tr class="{{ $purchase->isVoided() ? 'opacity-60' : '' }}">
                                        <td class="px-4 py-3 whitespace-nowrap {{ $purchase->isVoided() ? 'line-through text-gray-400' : 'text-gray-600' }}">{{ $purchase->purchase_date->format('d F Y') }}</td>
                                        <td class="px-4 py-3 {{ $purchase->isVoided() ? 'line-through text-gray-400' : 'text-gray-800' }}">
                                            {{ $purchase->description }}
                                            @if ($purchase->notes)
                                                <p class="text-xs text-gray-400">{{ $purchase->notes }}</p>
                                            @endif
                                            @if ($purchase->isVoided())
                                                <p class="text-xs text-red-500 mt-0.5 no-underline">
                                                    <span class="inline-flex rounded-full px-2 py-0.5 bg-red-100 text-red-700 font-medium">Voided</span>
                                                    {{ $purchase->voidedBy?->name }} · {{ $purchase->voided_at->format('d/m/Y H:i') }}: {{ $purchase->void_reason }}
                                                </p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap {{ $purchase->isVoided() ? 'line-through text-gray-400' : 'text-gray-600' }}">{{ $purchase->supplier_name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-right font-medium whitespace-nowrap {{ $purchase->isVoided() ? 'line-through text-gray-400' : 'text-gray-900' }}">RM {{ number_format($purchase->amount, 2) }}</td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <button type="button" @click="manageOpenId = manageOpenId === {{ $purchase->id }} ? null : {{ $purchase->id }}" class="text-amber-600 hover:underline text-xs">
                                                Manage
                                            </button>
                                        </td>
                                    </tr>
                                    <tr x-show="manageOpenId === {{ $purchase->id }}" x-cloak x-data="{ editing: false, showLog: false }">
                                        <td colspan="5" class="px-4 py-3 bg-gray-50">
                                            <div class="flex flex-wrap items-center gap-4 text-xs">
                                                @if ($purchase->receipt_path)
                                                    <a href="{{ Storage::url($purchase->receipt_path) }}" target="_blank" class="text-amber-600 hover:underline">Lihat Resit</a>
                                                    @if ($purchase->drive_backed_up_at)
                                                        <span class="text-green-600" title="Disandarkan ke Google Drive pada {{ $purchase->drive_backed_up_at->format('d/m/Y H:i') }}">Backup Completed in Google Drive</span>
                                                    @else
                                                        <span class="text-gray-400" title="Akan disandarkan pada backup malam seterusnya">Pending backup on 7:00 PM</span>
                                                    @endif
                                                @else
                                                    <span class="text-gray-300">Tiada resit</span>
                                                @endif

                                                @if (auth()->user()->hasFullAccess() && ! $purchase->isVoided())
                                                    <button type="button" @click="editing = ! editing" class="text-gray-600 hover:underline">
                                                        <span x-text="editing ? 'Batal Edit' : 'Edit'"></span>
                                                    </button>

                                                    <form method="POST" action="{{ route('purchases.void', $purchase) }}" onsubmit="return submitVoidForm(this)">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="void_reason">
                                                        <button type="submit" class="text-red-500 hover:underline">Void</button>
                                                    </form>
                                                @endif

                                                @if ($purchase->edits->isNotEmpty() || $purchase->isVoided())
                                                    <button type="button" @click="showLog = ! showLog" class="text-gray-400 hover:underline">
                                                        <span x-text="showLog ? 'Sorok Log' : 'Log'"></span>
                                                    </button>
                                                @endif
                                            </div>

                                            <div x-show="editing" x-cloak class="mt-3 pt-3 border-t border-gray-200">
                                                <form method="POST" action="{{ route('purchases.update', $purchase) }}" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                                    @csrf
                                                    @method('PATCH')
                                                    <div>
                                                        <label class="block text-xs text-gray-500 mb-1">Tarikh</label>
                                                        <input type="date" name="purchase_date" value="{{ $purchase->purchase_date->toDateString() }}" required class="rounded-md border-gray-300 shadow-sm text-sm w-full">
                                                    </div>
                                                    <div class="sm:col-span-2">
                                                        <label class="block text-xs text-gray-500 mb-1">Keterangan</label>
                                                        <input type="text" name="description" value="{{ $purchase->description }}" required class="rounded-md border-gray-300 shadow-sm text-sm w-full">
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs text-gray-500 mb-1">Pembekal</label>
                                                        <input type="text" name="supplier_name" value="{{ $purchase->supplier_name }}" class="rounded-md border-gray-300 shadow-sm text-sm w-full">
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs text-gray-500 mb-1">Jumlah (RM)</label>
                                                        <input type="number" step="0.01" min="0" name="amount" value="{{ $purchase->amount }}" required class="rounded-md border-gray-300 shadow-sm text-sm w-full">
                                                    </div>
                                                    @unless ($purchase->receipt_path)
                                                        <div class="sm:col-span-3">
                                                            <label class="block text-xs text-gray-500 mb-1">Tambah Resit</label>
                                                            <input type="file" name="receipt" accept="image/*,.pdf" class="block w-full text-xs text-gray-600">
                                                        </div>
                                                    @endunless
                                                    <div class="sm:col-span-3">
                                                        <label class="block text-xs text-gray-500 mb-1">Nota</label>
                                                        <input type="text" name="notes" value="{{ $purchase->notes }}" class="rounded-md border-gray-300 shadow-sm text-sm w-full">
                                                    </div>
                                                    <div class="flex items-end">
                                                        <x-primary-button type="submit" class="!py-1.5 !px-3 text-xs">Simpan</x-primary-button>
                                                    </div>
                                                </form>
                                            </div>

                                            <div x-show="showLog" x-cloak class="mt-3 pt-3 border-t border-gray-200 space-y-1.5">
                                                @if ($purchase->isVoided())
                                                    <p class="text-xs text-gray-500">
                                                        <span class="font-medium text-red-600">Void</span> oleh {{ $purchase->voidedBy?->name }}, {{ $purchase->voided_at->format('d F Y, H:i') }}<br>
                                                        Sebab: {{ $purchase->void_reason }}
                                                    </p>
                                                @endif
                                                @foreach ($purchase->edits as $edit)
                                                    <p class="text-xs text-gray-500">
                                                        <span class="font-medium">Edit</span> oleh {{ $edit->editedBy->name }}, {{ $edit->created_at->format('d F Y, H:i') }}<br>
                                                        <span class="whitespace-pre-line">{{ $edit->changes }}</span>
                                                    </p>
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            <div class="mt-4">
                {{ $purchases->links() }}
            </div>
        </div>
    </div>

    <script>
        function submitVoidForm(form) {
            const reason = prompt('Sebab void belian ini:');
            if (!reason || !reason.trim()) return false;
            form.void_reason.value = reason.trim();
            return true;
        }
    </script>
</x-app-layout>
