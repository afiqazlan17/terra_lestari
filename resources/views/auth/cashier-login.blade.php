<x-guest-layout>
    <div x-data="cashierPin()" x-init="init()">
        <h2 class="text-center text-lg font-semibold text-gray-800 mb-1">Log Masuk Cashier</h2>
        <p class="text-center text-sm text-gray-500 mb-6">Masukkan PIN 4 digit</p>

        <x-input-error :messages="$errors->get('pin')" class="mb-4 text-center" />

        <form method="POST" action="{{ route('cashier-login.attempt') }}" x-ref="form">
            @csrf
            <input type="hidden" name="pin" x-model="pin">

            <div class="flex justify-center gap-3 mb-8">
                <template x-for="i in 4" :key="i">
                    <div class="w-4 h-4 rounded-full border-2 border-amber-500"
                        :class="pin.length >= i ? 'bg-amber-500' : 'bg-transparent'"></div>
                </template>
            </div>

            <div class="grid grid-cols-3 gap-3 max-w-xs mx-auto">
                <template x-for="digit in [1,2,3,4,5,6,7,8,9]" :key="digit">
                    <button type="button" @click="press(digit)"
                        class="py-4 text-xl font-medium rounded-lg bg-gray-100 hover:bg-gray-200 active:scale-95 transition">
                        <span x-text="digit"></span>
                    </button>
                </template>
                <button type="button" @click="clear()"
                    class="py-4 text-sm font-medium rounded-lg bg-gray-50 hover:bg-gray-100 text-gray-500">
                    Padam
                </button>
                <button type="button" @click="press(0)"
                    class="py-4 text-xl font-medium rounded-lg bg-gray-100 hover:bg-gray-200 active:scale-95 transition">
                    0
                </button>
                <button type="button" @click="backspace()"
                    class="py-4 text-sm font-medium rounded-lg bg-gray-50 hover:bg-gray-100 text-gray-500">
                    &larr;
                </button>
            </div>
        </form>

        <div class="text-center mt-6">
            <a href="{{ route('login') }}" class="text-sm text-gray-500 hover:underline">Log masuk sebagai Owner/Manager</a>
        </div>
    </div>

    <script>
        function cashierPin() {
            return {
                pin: '',
                init() {
                    @if ($errors->has('pin'))
                        this.pin = '';
                    @endif
                },
                press(digit) {
                    if (this.pin.length >= 4) {
                        return;
                    }
                    this.pin += digit;
                    if (this.pin.length === 4) {
                        this.$nextTick(() => this.$refs.form.submit());
                    }
                },
                backspace() {
                    this.pin = this.pin.slice(0, -1);
                },
                clear() {
                    this.pin = '';
                },
            };
        }
    </script>
</x-guest-layout>
