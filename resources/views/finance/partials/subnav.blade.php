<div class="flex gap-6 border-b border-gray-200 mb-6 text-sm">
    <a href="{{ route('finance.index', request()->query()) }}"
        class="pb-3 border-b-2 {{ request()->routeIs('finance.index') ? 'border-amber-500 text-amber-600 font-medium' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
        Untung Rugi
    </a>
    <a href="{{ route('finance.cashbook', request()->query()) }}"
        class="pb-3 border-b-2 {{ request()->routeIs('finance.cashbook') ? 'border-amber-500 text-amber-600 font-medium' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
        Cash Book
    </a>
</div>
