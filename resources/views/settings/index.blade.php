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

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase mb-4">Lebar Kertas Resit</h3>
                <form method="POST" action="{{ route('settings.paper-width') }}" class="flex items-center gap-3">
                    @csrf
                    <select name="receipt_paper_width" class="rounded-md border-gray-300 shadow-sm text-sm" onchange="this.form.submit()">
                        <option value="58mm" @selected($project->receipt_paper_width === '58mm')>58mm</option>
                        <option value="80mm" @selected($project->receipt_paper_width === '80mm')>80mm</option>
                    </select>
                    <noscript><x-primary-button type="submit">{{ __('Save') }}</x-primary-button></noscript>
                </form>
                <p class="text-xs text-gray-400 mt-2">Pilih ikut lebar kertas thermal printer resit yang digunakan.</p>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6" x-data="printerSettings()" x-init="init()">
                <h3 class="text-sm font-semibold text-gray-500 uppercase mb-1">Printer Resit (Bluetooth)</h3>
                <p class="text-xs text-gray-400 mb-4">Sambungan di sini untuk uji printer sahaja (Test Print, Buka Laci) &mdash; ia tak dibawa bila tukar ke page lain. Untuk operasi jualan harian, sambung terus di page <strong>POS</strong> (butang "Sambung Printer" sebelum Checkout).</p>

                <template x-if="! supported">
                    <p class="text-sm text-red-600">Bluetooth printing tidak disokong oleh browser ini.</p>
                </template>

                <template x-if="supported">
                    <div>
                        <p class="text-sm mb-3">
                            <span x-show="connected" class="text-green-700 font-medium">Disambung: <span x-text="printerName"></span></span>
                            <span x-show="! connected" class="text-gray-500">Tiada printer disambung.</span>
                        </p>

                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" @click="connect()" :disabled="busy"
                                class="bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                                <span x-text="connected ? 'Sambung Semula / Tukar Printer' : 'Sambung Printer'"></span>
                            </button>
                            <button type="button" x-show="connected" @click="testPrint()" :disabled="busy"
                                class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg disabled:opacity-50">
                                Test Print
                            </button>
                            <button type="button" x-show="connected" @click="testOpenDrawer(0)" :disabled="busy"
                                class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg disabled:opacity-50">
                                Buka Laci (Pin 2)
                            </button>
                            <button type="button" x-show="connected" @click="testOpenDrawer(1)" :disabled="busy"
                                class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg disabled:opacity-50">
                                Buka Laci (Pin 5)
                            </button>
                            <button type="button" x-show="connected" @click="testOpenDrawerAlt()" :disabled="busy"
                                class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg disabled:opacity-50">
                                Buka Laci (Mod 2)
                            </button>
                            <button type="button" x-show="connected" @click="testOpenDrawerLong()" :disabled="busy"
                                class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg disabled:opacity-50">
                                Buka Laci (Pulse Panjang)
                            </button>
                            <button type="button" x-show="connected" @click="disconnect()"
                                class="text-sm text-red-500 hover:underline px-2 py-2">
                                Putuskan Sambungan
                            </button>
                        </div>

                        <p class="text-xs mt-3" :class="statusIsError ? 'text-red-600' : 'text-gray-400'" x-text="status"></p>
                    </div>
                </template>
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

    <script>
        function printerSettings() {
            return {
                supported: false,
                connected: false,
                printerName: '',
                busy: false,
                status: '',
                statusIsError: false,

                async init() {
                    this.supported = window.SBPrinter ? window.SBPrinter.isSupported() : false;
                    if (! this.supported) {
                        return;
                    }

                    await new Promise((resolve) => setTimeout(resolve, 300));
                    this.refreshState();
                },

                refreshState() {
                    this.connected = window.SBPrinter.isConnected();
                    const saved = window.SBPrinter.getSavedConfig();
                    this.printerName = (window.SBPrinter.device && window.SBPrinter.device.name) || (saved && saved.deviceName) || '';
                },

                async connect() {
                    this.busy = true;
                    this.status = '';
                    try {
                        const result = await window.SBPrinter.connect();
                        this.printerName = result.name;
                        this.connected = true;
                        this.status = 'Berjaya disambung.';
                        this.statusIsError = false;
                    } catch (e) {
                        this.status = 'Gagal sambung: ' + e.message;
                        this.statusIsError = true;
                    }
                    this.busy = false;
                },

                async testPrint() {
                    this.busy = true;
                    this.status = '';
                    try {
                        const bytes = window.buildReceiptEscPos({
                            orderNumber: 'TEST',
                            dateStr: new Date().toLocaleString('ms-MY'),
                            typeLabel: 'Test Print',
                            items: [{ name: 'Item Ujian', qty: 1, price: '1.00', lineTotal: '1.00' }],
                            subtotal: '1.00',
                            discount: 0,
                            total: '1.00',
                            paymentLabel: 'Test',
                        }, {{ $project->receipt_paper_width === '58mm' ? 'true' : 'false' }});
                        await window.SBPrinter.write(bytes);
                        this.status = 'Test print dihantar.';
                        this.statusIsError = false;
                    } catch (e) {
                        this.status = 'Gagal print: ' + e.message;
                        this.statusIsError = true;
                    }
                    this.busy = false;
                },

                async testOpenDrawer(pin) {
                    this.busy = true;
                    this.status = '';
                    try {
                        await window.SBPrinter.write(window.buildOpenDrawerCommand(pin));
                        this.status = 'Arahan buka laci dihantar (Pin ' + (pin === 0 ? '2' : '5') + ').';
                        this.statusIsError = false;
                    } catch (e) {
                        this.status = 'Gagal buka laci: ' + e.message;
                        this.statusIsError = true;
                    }
                    this.busy = false;
                },

                async testOpenDrawerAlt() {
                    this.busy = true;
                    this.status = '';
                    try {
                        await window.SBPrinter.write(window.buildOpenDrawerCommandAlt());
                        this.status = 'Arahan buka laci dihantar (Mod 2).';
                        this.statusIsError = false;
                    } catch (e) {
                        this.status = 'Gagal buka laci: ' + e.message;
                        this.statusIsError = true;
                    }
                    this.busy = false;
                },

                async testOpenDrawerLong() {
                    this.busy = true;
                    this.status = '';
                    try {
                        await window.SBPrinter.write(window.buildOpenDrawerCommand(0, 200, 200));
                        this.status = 'Arahan buka laci dihantar (pulse panjang).';
                        this.statusIsError = false;
                    } catch (e) {
                        this.status = 'Gagal buka laci: ' + e.message;
                        this.statusIsError = true;
                    }
                    this.busy = false;
                },

                disconnect() {
                    window.SBPrinter.disconnect();
                    this.connected = false;
                    this.printerName = '';
                    this.status = 'Sambungan diputuskan.';
                    this.statusIsError = false;
                },
            };
        }
    </script>
</x-app-layout>
