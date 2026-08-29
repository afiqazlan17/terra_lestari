<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Perbelanjaan</h2>
            <a href="{{ route('expenses.create') }}" class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                + Rekod Perbelanjaan
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                @if ($expenses->isEmpty())
                    <p class="p-8 text-center text-gray-400">Belum ada perbelanjaan direkodkan.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs text-gray-500 uppercase">
                                    <th class="px-4 py-3 whitespace-nowrap">Tarikh</th>
                                    <th class="px-4 py-3 whitespace-nowrap">Kategori</th>
                                    <th class="px-4 py-3">Keterangan</th>
                                    <th class="px-4 py-3 whitespace-nowrap">Pembekal/Penerima</th>
                                    <th class="px-4 py-3 whitespace-nowrap">Resit</th>
                                    <th class="px-4 py-3 text-right whitespace-nowrap">Jumlah</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($expenses as $expense)
                                    <tr class="{{ $expense->isVoided() ? 'opacity-60' : '' }}">
                                        <td class="px-4 py-3 whitespace-nowrap {{ $expense->isVoided() ? 'line-through text-gray-400' : 'text-gray-600' }}">{{ $expense->purchase_date->format('d F Y') }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="inline-flex rounded-full px-2 py-1 text-xs bg-gray-100 text-gray-600 {{ $expense->isVoided() ? 'line-through' : '' }}">
                                                {{ $expense->categoryLabel() }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 {{ $expense->isVoided() ? 'line-through text-gray-400' : 'text-gray-800' }}">
                                            {{ $expense->description }}
                                            @if ($expense->notes)
                                                <p class="text-xs text-gray-400">{{ $expense->notes }}</p>
                                            @endif
                                            @if ($expense->isVoided())
                                                <p class="text-xs text-red-500 mt-0.5 no-underline">
                                                    <span class="inline-flex rounded-full px-2 py-0.5 bg-red-100 text-red-700 font-medium">Voided</span>
                                                    {{ $expense->voidedBy?->name }} · {{ $expense->voided_at->format('d/m/Y H:i') }}: {{ $expense->void_reason }}
                                                </p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap {{ $expense->isVoided() ? 'line-through text-gray-400' : 'text-gray-600' }}">{{ $expense->supplier_name ?? '-' }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if ($expense->receipt_path)
                                                <a href="{{ Storage::url($expense->receipt_path) }}" target="_blank" class="text-amber-600 hover:underline">Lihat</a>
                                            @else
                                                <span class="text-gray-300">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium whitespace-nowrap {{ $expense->isVoided() ? 'line-through text-gray-400' : 'text-gray-900' }}">RM {{ number_format($expense->amount, 2) }}</td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            @if (auth()->user()->hasFullAccess() && ! $expense->isVoided())
                                                <form method="POST" action="{{ route('expenses.void', $expense) }}" onsubmit="return submitVoidForm(this)">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="void_reason">
                                                    <button type="submit" class="text-red-500 hover:underline text-xs">Void</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            <div class="mt-4">
                {{ $expenses->links() }}
            </div>
        </div>
    </div>

    <script>
        function submitVoidForm(form) {
            const reason = prompt('Sebab void perbelanjaan ini:');
            if (!reason || !reason.trim()) return false;
            form.void_reason.value = reason.trim();
            return true;
        }
    </script>
</x-app-layout>
