@props(['active' => false])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-indigo-400 text-sm font-medium leading-5 text-gray-900 focus:outline-none focus:border-indigo-700 transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out';
@endphp

{{--
    The nav-link box styling (stretch-to-row-height, bottom border) has to
    live on THIS div, not the button inside it - a flex row only stretches
    its direct children to full height, so a button nested one level deeper
    just sits at its own natural (short) height inside a stretched-but-empty
    wrapper, throwing off the shared bottom-aligned underline row.
--}}
<div class="relative {{ $classes }}" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
    <button type="button" @click="open = ! open" class="flex items-center focus:outline-none">
        {{ $slot }}
        <svg class="ms-1 h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
    </button>

    <div x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute z-50 mt-2 w-48 rounded-md shadow-lg ltr:origin-top-left rtl:origin-top-right start-0"
            style="display: none;"
            @click="open = false">
        <div class="rounded-md ring-1 ring-black ring-opacity-5 py-1 bg-white">
            {{ $content }}
        </div>
    </div>
</div>
