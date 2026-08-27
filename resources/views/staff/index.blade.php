<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Staff</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase mb-4">Tambah Akaun Staff</h3>
                <form method="POST" action="{{ route('staff.store') }}" x-data="{ role: '{{ old('role', 'cashier') }}' }" class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
                    @csrf
                    <div>
                        <x-input-label for="name" value="Nama" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                    </div>
                    <div>
                        <x-input-label for="email" value="Email" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                    </div>
                    <div>
                        <x-input-label for="role" value="Peranan" />
                        <select id="role" name="role" x-model="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="cashier">Cashier (POS sahaja)</option>
                            <option value="manager">Manager (Dashboard, Belian, Menu)</option>
                        </select>
                    </div>
                    <div x-show="role === 'cashier'">
                        <x-input-label for="pin" value="PIN (4 digit)" />
                        <x-text-input id="pin" name="pin" type="text" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" class="mt-1 block w-full" />
                    </div>
                    <div x-show="role === 'manager'" x-cloak>
                        <x-input-label for="password" value="Password" />
                        <x-password-input id="password" name="password" class="mt-1 block w-full" />
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-xs text-gray-400 -mt-2 mb-1" x-show="role === 'cashier'">PIN untuk log masuk di halaman "Log Masuk Cashier".</p>
                        <x-input-error :messages="$errors->all()" class="mt-1" />
                        <x-primary-button type="submit">Tambah Staff</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden" x-data="{ editingPinId: null }">
                @if ($staff->isEmpty())
                    <p class="p-8 text-center text-gray-400">Belum ada staff.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs text-gray-500 uppercase">
                                    <th class="px-4 py-3 whitespace-nowrap">Nama</th>
                                    <th class="px-4 py-3 whitespace-nowrap">Email</th>
                                    <th class="px-4 py-3 whitespace-nowrap">Peranan</th>
                                    <th class="px-4 py-3 whitespace-nowrap">Status</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($staff as $member)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-800 whitespace-nowrap">{{ $member->name }}</td>
                                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $member->email }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="inline-flex rounded-full px-2 py-1 text-xs {{ $member->isManager() ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600' }}">
                                                {{ $member->isManager() ? 'Manager' : 'Cashier' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="inline-flex rounded-full px-2 py-1 text-xs {{ $member->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                                {{ $member->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap space-x-3">
                                            @if ($member->isCashier())
                                                <button type="button" @click="editingPinId = editingPinId === {{ $member->id }} ? null : {{ $member->id }}" class="text-amber-600 hover:underline text-xs">
                                                    Tukar PIN
                                                </button>
                                            @endif
                                            <form method="POST" action="{{ route('staff.toggle', $member) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-amber-600 hover:underline text-xs">
                                                    {{ $member->is_active ? 'Nyahaktifkan' : 'Aktifkan' }}
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('staff.destroy', $member) }}" class="inline" onsubmit="return confirm('Padam staff ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:underline text-xs">Padam</button>
                                            </form>
                                        </td>
                                    </tr>
                                    @if ($member->isCashier())
                                        <tr x-show="editingPinId === {{ $member->id }}" x-cloak>
                                            <td colspan="5" class="px-4 py-3 bg-gray-50">
                                                <form method="POST" action="{{ route('staff.pin.update', $member) }}" class="flex flex-wrap items-center gap-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <label class="text-xs text-gray-500">PIN baru (4 digit)</label>
                                                    <input type="text" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" name="pin" required class="rounded-md border-gray-300 shadow-sm text-sm w-24">
                                                    <x-primary-button type="submit" class="!py-1.5 !px-3 text-xs">Simpan</x-primary-button>
                                                    <button type="button" @click="editingPinId = null" class="text-xs text-gray-500 hover:underline">Batal</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
