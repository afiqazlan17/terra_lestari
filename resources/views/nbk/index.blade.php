<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">NBK GROUP EMPIRE (M) SDN BHD</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-3 gap-4">
                <a href="{{ route('nbk.orders.index') }}"
                    class="aspect-square bg-white shadow-sm rounded-xl border border-gray-100 hover:border-amber-300 hover:shadow-md transition flex flex-col items-center justify-center gap-2 p-4 text-center">
                    <span class="text-3xl">📋</span>
                    <span class="font-semibold text-gray-800">Senarai Order</span>
                </a>
                <a href="{{ route('nbk.orders.create') }}"
                    class="aspect-square bg-white shadow-sm rounded-xl border border-gray-100 hover:border-amber-300 hover:shadow-md transition flex flex-col items-center justify-center gap-2 p-4 text-center">
                    <span class="text-3xl">🧾</span>
                    <span class="font-semibold text-gray-800">Upload Invoice</span>
                </a>
                <a href="{{ route('nbk.products.index') }}"
                    class="aspect-square bg-white shadow-sm rounded-xl border border-gray-100 hover:border-amber-300 hover:shadow-md transition flex flex-col items-center justify-center gap-2 p-4 text-center">
                    <span class="text-3xl">📦</span>
                    <span class="font-semibold text-gray-800">Urus Katalog</span>
                </a>
            </div>

            @if ($baki)
                <div class="mt-4 bg-white shadow-sm rounded-xl border border-gray-100 p-4">
                    <div class="flex items-baseline justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-800">Baki NBK Hari Ini</h3>
                        <span class="text-xs text-gray-500">{{ now()->translatedFormat('d F Y') }}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        @foreach (['nasi' => 'Nasi', 'kuih' => 'Kuih Muih'] as $key => $label)
                            <div class="border border-gray-100 rounded-lg p-3">
                                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">{{ $label }}</p>
                                <div class="flex justify-between text-gray-600">
                                    <span>Order</span>
                                    <span class="font-medium text-gray-800">{{ $baki[$key]['ordered'] }} unit</span>
                                </div>
                                <div class="flex justify-between text-gray-600">
                                    <span>Terjual</span>
                                    <span class="font-medium text-gray-800">{{ $baki[$key]['sold'] }} unit</span>
                                </div>
                                <div class="flex justify-between font-semibold text-gray-900 border-t border-gray-100 mt-1 pt-1">
                                    <span>Baki</span>
                                    <span>{{ $baki[$key]['baki'] }} unit</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
