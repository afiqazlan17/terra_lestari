<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">NBK</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 gap-4">
                <a href="{{ route('nbk.orders.index') }}"
                    class="aspect-square bg-white shadow-sm rounded-xl border border-gray-100 hover:border-amber-300 hover:shadow-md transition flex flex-col items-center justify-center gap-2 p-4 text-center">
                    <span class="text-3xl">📋</span>
                    <span class="font-semibold text-gray-800">Sejarah Order</span>
                </a>
                <a href="{{ route('nbk.products.index') }}"
                    class="aspect-square bg-white shadow-sm rounded-xl border border-gray-100 hover:border-amber-300 hover:shadow-md transition flex flex-col items-center justify-center gap-2 p-4 text-center">
                    <span class="text-3xl">📦</span>
                    <span class="font-semibold text-gray-800">Urus Katalog</span>
                </a>
                <a href="{{ route('nbk.orders.create') }}"
                    class="aspect-square bg-white shadow-sm rounded-xl border border-gray-100 hover:border-amber-300 hover:shadow-md transition flex flex-col items-center justify-center gap-2 p-4 text-center">
                    <span class="text-3xl">🧮</span>
                    <span class="font-semibold text-gray-800">Kalkulator Order</span>
                </a>
                <a href="{{ route('nbk.orders.create') }}#invois-upload"
                    class="aspect-square bg-white shadow-sm rounded-xl border border-gray-100 hover:border-amber-300 hover:shadow-md transition flex flex-col items-center justify-center gap-2 p-4 text-center">
                    <span class="text-3xl">🧾</span>
                    <span class="font-semibold text-gray-800">Upload Invois NBK</span>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
