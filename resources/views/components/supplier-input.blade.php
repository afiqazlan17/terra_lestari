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

<div x-data="sbSupplierInput(@js($suppliers), @js($value))" class="relative">
    <input type="text" id="{{ $id ?? $name }}" name="{{ $name }}" autocomplete="off"
        x-model="query" @focus="open = true" @input="open = true" @click.outside="open = false"
        {{ $attributes->merge(['class' => 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full']) }}>

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
