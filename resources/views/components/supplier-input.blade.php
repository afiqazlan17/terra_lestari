@props(['name' => 'supplier_name', 'id' => null, 'value' => null, 'suppliers' => []])

@once
<script>
    function sbSupplierInput(suppliers, initial) {
        return {
            suppliers: suppliers || [],
            query: initial || '',
            open: false,

            get suggestion() {
                const q = (this.query || '').trim().toLowerCase();
                if (! q) return null;

                const exact = this.suppliers.find((s) => s.toLowerCase() === q);
                if (exact) return null;

                return this.suppliers.find((s) => {
                    const sl = s.toLowerCase();
                    return sl.includes(q) || q.includes(sl);
                }) || null;
            },

            filtered() {
                const q = (this.query || '').trim().toLowerCase();
                const list = q ? this.suppliers.filter((s) => s.toLowerCase().includes(q)) : this.suppliers;
                return list.slice(0, 8);
            },

            select(s) {
                this.query = s;
                this.open = false;
            },

            acceptSuggestion() {
                this.query = this.suggestion;
            },
        };
    }
</script>
@endonce

<div x-data="sbSupplierInput(@js($suppliers), @js($value))" class="relative" @click.outside="open = false">
    <input type="text" x-ref="input" id="{{ $id ?? $name }}" name="{{ $name }}" autocomplete="off"
        x-model="query" @focus="open = true" @input="open = true"
        {{ $attributes->merge(['class' => 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full pr-7']) }}>
    <button type="button" tabindex="-1" @click="open = ! open; $refs.input.focus()"
        class="absolute inset-y-0 right-0 flex items-center pr-2 text-gray-400">
        <svg class="h-4 w-4" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 20 20" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5.5 7.5l4.5 4.5 4.5-4.5" />
        </svg>
    </button>

    <template x-if="open && filtered().length > 0">
        <ul class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-48 overflow-y-auto text-sm">
            <template x-for="s in filtered()" :key="s">
                <li @click="select(s)" class="px-3 py-2 hover:bg-amber-50 cursor-pointer text-gray-700" x-text="s"></li>
            </template>
        </ul>
    </template>

    <template x-if="suggestion && ! open">
        <p class="text-xs text-amber-700 mt-1">
            Adakah maksud anda:
            <button type="button" @click="acceptSuggestion()" class="underline font-medium" x-text="suggestion"></button>?
        </p>
    </template>
</div>
