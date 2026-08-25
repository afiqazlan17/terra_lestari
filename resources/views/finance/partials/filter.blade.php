<form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex flex-wrap items-end gap-3">
    <div>
        <label class="block text-xs text-gray-500 mb-1">Dari</label>
        <input type="date" name="from" value="{{ $from->toDateString() }}" class="rounded-md border-gray-300 shadow-sm text-sm">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Hingga</label>
        <input type="date" name="to" value="{{ $to->toDateString() }}" class="rounded-md border-gray-300 shadow-sm text-sm">
    </div>
    <x-secondary-button type="submit">Tapis</x-secondary-button>
    <div class="flex gap-2 text-xs text-gray-500 ms-auto">
        <a href="?from={{ now()->startOfMonth()->toDateString() }}&to={{ now()->endOfDay()->toDateString() }}" class="hover:underline hover:text-amber-600">Bulan Ini</a>
        <span>&middot;</span>
        <a href="?from={{ now()->startOfWeek()->toDateString() }}&to={{ now()->endOfDay()->toDateString() }}" class="hover:underline hover:text-amber-600">Minggu Ini</a>
        <span>&middot;</span>
        <a href="?from={{ now()->subMonthsNoOverflow(1)->startOfMonth()->toDateString() }}&to={{ now()->subMonthsNoOverflow(1)->endOfMonth()->toDateString() }}" class="hover:underline hover:text-amber-600">Bulan Lalu</a>
    </div>
</form>
