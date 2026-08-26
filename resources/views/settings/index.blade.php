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
                <div class="bg-white shadow-sm sm:rounded-lg p-6 border border-red-200" x-data="{ open: false, selected: [] }">
                    <h3 class="text-sm font-semibold text-red-600 uppercase mb-2">{{ __('Danger Zone') }}</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        {{ __('Reset selected transaction data. Menu and staff accounts are always kept, regardless of what you select.') }}
                    </p>

                    <button type="button" @click="open = true" x-show="! open"
                        class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                        {{ __('Reset Data') }}
                    </button>

                    <form x-show="open" x-cloak method="POST" action="{{ route('settings.reset-data') }}"
                        onsubmit="return selected.length > 0 && confirm('{{ __('Are you absolutely sure? This cannot be undone.') }}')">
                        @csrf

                        <p class="text-sm text-gray-700 mb-2">{{ __('Pilih data yang nak direset:') }}</p>
                        <div class="space-y-2 mb-4">
                            <label class="flex items-center justify-between gap-3 border border-gray-200 rounded-md px-3 py-2 text-sm">
                                <span class="flex items-center gap-2">
                                    <input type="checkbox" name="categories[]" value="orders" x-model="selected" class="rounded border-gray-300">
                                    Jualan (Order POS)
                                </span>
                                <span class="text-gray-400">{{ number_format($counts['orders']) }} rekod</span>
                            </label>
                            <label class="flex items-center justify-between gap-3 border border-gray-200 rounded-md px-3 py-2 text-sm">
                                <span class="flex items-center gap-2">
                                    <input type="checkbox" name="categories[]" value="belian" x-model="selected" class="rounded border-gray-300">
                                    Belian (Bahan Mentah)
                                </span>
                                <span class="text-gray-400">{{ number_format($counts['belian']) }} rekod</span>
                            </label>
                            <label class="flex items-center justify-between gap-3 border border-gray-200 rounded-md px-3 py-2 text-sm">
                                <span class="flex items-center gap-2">
                                    <input type="checkbox" name="categories[]" value="perbelanjaan" x-model="selected" class="rounded border-gray-300">
                                    Perbelanjaan
                                </span>
                                <span class="text-gray-400">{{ number_format($counts['perbelanjaan']) }} rekod</span>
                            </label>
                            <label class="flex items-center justify-between gap-3 border border-gray-200 rounded-md px-3 py-2 text-sm">
                                <span class="flex items-center gap-2">
                                    <input type="checkbox" name="categories[]" value="capital_injections" x-model="selected" class="rounded border-gray-300">
                                    Modal Awal
                                </span>
                                <span class="text-gray-400">{{ number_format($counts['capital_injections']) }} rekod</span>
                            </label>
                            <label class="flex items-center justify-between gap-3 border border-gray-200 rounded-md px-3 py-2 text-sm">
                                <span class="flex items-center gap-2">
                                    <input type="checkbox" name="categories[]" value="daily_sessions" x-model="selected" class="rounded border-gray-300">
                                    Sesi Harian (Buka/Tutup Hari)
                                </span>
                                <span class="text-gray-400">{{ number_format($counts['daily_sessions']) }} rekod</span>
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('categories')" class="mb-2" />

                        <label class="block text-sm text-gray-700 mb-2">
                            {{ __('Type RESET to confirm') }}
                        </label>
                        <div class="flex items-center gap-3 flex-wrap">
                            <input type="text" name="confirm" required placeholder="RESET"
                                class="rounded-md border-gray-300 shadow-sm text-sm">
                            <button type="submit" :disabled="selected.length === 0"
                                class="bg-red-600 hover:bg-red-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-sm font-semibold px-4 py-2 rounded-lg">
                                {{ __('Confirm Reset') }}
                            </button>
                            <button type="button" @click="open = false; selected = []" class="text-sm text-gray-500 hover:underline">
                                {{ __('Cancel') }}
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
