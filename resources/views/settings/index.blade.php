<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Settings') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase mb-4">{{ __('Language') }}</h3>
                <form method="POST" action="{{ route('settings.locale') }}" class="flex items-center gap-3">
                    @csrf
                    <select name="locale" class="rounded-md border-gray-300 shadow-sm text-sm" onchange="this.form.submit()">
                        <option value="ms" @selected($user->locale === 'ms')>Bahasa Melayu</option>
                        <option value="en" @selected($user->locale === 'en')>English</option>
                    </select>
                    <noscript><x-primary-button type="submit">{{ __('Save') }}</x-primary-button></noscript>
                </form>
            </div>

            @if ($user->isSuperuser())
                <div class="bg-white shadow-sm sm:rounded-lg p-6 border border-red-200" x-data="{ open: false }">
                    <h3 class="text-sm font-semibold text-red-600 uppercase mb-2">{{ __('Danger Zone') }}</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        {{ __('Reset all transaction data (sales, purchases, capital injections, daily sessions). Menu and staff accounts are kept.') }}
                    </p>

                    <button type="button" @click="open = true" x-show="! open"
                        class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                        {{ __('Reset All Data') }}
                    </button>

                    <form x-show="open" x-cloak method="POST" action="{{ route('settings.reset-data') }}"
                        onsubmit="return confirm('{{ __('Are you absolutely sure? This cannot be undone.') }}')">
                        @csrf
                        <label class="block text-sm text-gray-700 mb-2">
                            {{ __('Type RESET to confirm') }}
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="text" name="confirm" required placeholder="RESET"
                                class="rounded-md border-gray-300 shadow-sm text-sm">
                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                                {{ __('Confirm Reset') }}
                            </button>
                            <button type="button" @click="open = false" class="text-sm text-gray-500 hover:underline">
                                {{ __('Cancel') }}
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
