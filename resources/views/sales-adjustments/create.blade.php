<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pelarasan Jualan</h2>
            <a href="{{ route('finance.sales') }}" class="text-gray-500 hover:text-gray-700 text-sm px-2">
                &larr; Kembali ke Jualan
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-xs text-amber-800">
                Guna ni HANYA bila jualan sebenar sudah dikira dalam duit tunai/QR sebenar semasa Tutup Hari, tapi
                tak sempat di-key-in sistem masa tu (contoh: sistem down). Pelarasan ini akan tambah dalam Jualan
                untuk hari tersebut supaya Jangkaan padan dengan angka Sebenar yang dah direkod - bukan untuk
                tambah jualan baru yang tak pernah berlaku.
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('sales-adjustments.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="date" value="Tarikh (hari yang dah tutup)" />
                        <x-text-input id="date" name="date" type="date" class="mt-1 block w-full sm:w-64" :value="old('date')" required />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <x-input-label for="cash" value="Tunai (RM)" />
                            <x-text-input id="cash" name="cash" type="text" inputmode="decimal" data-money-input class="mt-1 block w-full" :value="old('cash')" />
                        </div>
                        <div>
                            <x-input-label for="qr" value="QR / DuitNow (RM)" />
                            <x-text-input id="qr" name="qr" type="text" inputmode="decimal" data-money-input class="mt-1 block w-full" :value="old('qr')" />
                        </div>
                        <div>
                            <x-input-label for="card" value="Kad (RM)" />
                            <x-text-input id="card" name="card" type="text" inputmode="decimal" data-money-input class="mt-1 block w-full" :value="old('card')" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="reason" value="Sebab" />
                        <x-text-input id="reason" name="reason" type="text" class="mt-1 block w-full"
                            :value="old('reason', 'Tidak sempat direkod semasa sistem down')" required />
                    </div>
                    <x-input-error :messages="$errors->all()" class="mt-1" />
                    <x-primary-button type="submit">Tambah Pelarasan</x-primary-button>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <h3 class="px-4 py-3 bg-gray-50 text-sm font-semibold text-gray-600">Sejarah Pelarasan</h3>
                @if ($history->isEmpty())
                    <p class="p-6 text-sm text-gray-400">Belum ada pelarasan direkodkan.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs text-gray-500 uppercase">
                                    <th class="px-4 py-2">Tarikh</th>
                                    <th class="px-4 py-2">Kaedah</th>
                                    <th class="px-4 py-2">Sebab</th>
                                    <th class="px-4 py-2 text-right">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($history as $order)
                                    @php $item = $order->items->first(fn ($i) => str_starts_with($i->product_name, 'Pelarasan Jualan')); @endphp
                                    <tr>
                                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $order->created_at->format('d F Y') }}</td>
                                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $order->paymentMethodLabel() }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ \Illuminate\Support\Str::between($item?->product_name ?? '', '(', ')') }}</td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-900 whitespace-nowrap">RM {{ number_format($order->total, 2) }}</td>
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
