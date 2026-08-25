<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Staff</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase mb-4">Tambah Akaun Staff</h3>
                <form method="POST" action="{{ route('staff.store') }}" x-data="{ role: '{{ old('role', 'cashier') }}' }" class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
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
                        <p class="text-xs text-gray-400 mt-1">Untuk log masuk di halaman "Log Masuk Cashier".</p>
                    </div>
                    <div x-show="role === 'manager'" x-cloak>
                        <x-input-label for="password" value="Password" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-error :messages="$errors->all()" class="mt-1" />
                        <x-primary-button type="submit">Tambah Staff</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                @if ($staff->isEmpty())
                    <p class="p-8 text-center text-gray-400">Belum ada staff.</p>
                @else
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs text-gray-500 uppercase">
                                <th class="px-4 py-3">Nama</th>
                                <th class="px-4 py-3">Email</th>
                                <th class="px-4 py-3">Peranan</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($staff as $member)
                                <tr>
                                    <td class="px-4 py-3 text-gray-800">{{ $member->name }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $member->email }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs {{ $member->isManager() ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $member->isManager() ? 'Manager' : 'Cashier' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs {{ $member->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $member->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right space-x-3">
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
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
