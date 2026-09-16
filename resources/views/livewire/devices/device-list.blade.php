<div wire:poll.30s>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">Perangkat (GPS Tracker)</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Kelola tracker GPS ternak: status, baterai, dan pemasangan ke hewan.
            </p>
        </div>
        <a href="{{ route('devices.create') }}" class="tt-btn-primary">
            + Tambah Perangkat
        </a>
    </div>

    @if (session('status'))
        <div class="mt-4 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-700 dark:bg-green-900/40 dark:text-green-200">
            {{ session('status') }}
        </div>
    @endif

    <div class="tt-card mt-5 overflow-hidden p-0">
        <div class="flex flex-col gap-3 border-b border-slate-200 p-4 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between">
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari nama / kode perangkat..."
                class="tt-input sm:w-72"
            >
            <div class="flex gap-2 text-sm">
                <button
                    type="button"
                    wire:click="$set('statusFilter', '')"
                    class="tt-badge cursor-pointer hover:opacity-80 @if($statusFilter === '') bg-slate-600 text-white @endif"
                >Semua</button>
                <button
                    type="button"
                    wire:click="$set('statusFilter', 'active')"
                    class="tt-badge cursor-pointer hover:opacity-80 @if($statusFilter === 'active') bg-slate-600 text-white @endif"
                >Aktif</button>
                <button
                    type="button"
                    wire:click="$set('statusFilter', 'inactive')"
                    class="tt-badge cursor-pointer hover:opacity-80 @if($statusFilter === 'inactive') bg-slate-600 text-white @endif"
                >Nonaktif</button>
                <button
                    type="button"
                    wire:click="$set('statusFilter', 'maintenance')"
                    class="tt-badge cursor-pointer hover:opacity-80 @if($statusFilter === 'maintenance') bg-slate-600 text-white @endif"
                >Perbaikan</button>
                <button
                    type="button"
                    wire:click="$set('statusFilter', 'lost')"
                    class="tt-badge cursor-pointer hover:opacity-80 @if($statusFilter === 'lost') bg-slate-600 text-white @endif"
                >Hilang</button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-100 text-xs uppercase text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Perangkat</th>
                        <th class="px-4 py-3">Status ONLINE</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Baterai</th>
                        <th class="px-4 py-3">Hewan</th>
                        <th class="px-4 py-3">Laporan Terakhir</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse ($devices as $device)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3">
                                <a href="{{ route('devices.show', $device) }}" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">
                                    {{ $device->name }}
                                </a>
                                <div class="text-xs text-slate-400">{{ $device->device_code }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @if ($device->isOnline())
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span> ONLINE
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-400">
                                        <span class="h-2 w-2 rounded-full bg-slate-400"></span> OFFLINE
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $statusBg = match ($device->status) {
                                        'active' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                        'maintenance' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                        'lost' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                        default => 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
                                    };
                                    $statusLabel = match ($device->status) {
                                        'active' => 'Aktif',
                                        'inactive' => 'Nonaktif',
                                        'maintenance' => 'Perbaikan',
                                        'lost' => 'Hilang',
                                        default => $device->status,
                                    };
                                @endphp
                                <span class="{{ $statusBg }} tt-badge">{{ $statusLabel }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="h-1.5 w-16 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-600">
                                        <div class="h-full rounded-full @if(($device->battery_level ?? 0) > 50) bg-emerald-500 @elseif(($device->battery_level ?? 0) > 20) bg-amber-400 @else bg-red-500 @endif"
                                             style="width: {{ $device->battery_level ?? 0 }}%"></div>
                                    </div>
                                    <span class="text-xs tabular-nums text-slate-600 dark:text-slate-300">{{ $device->battery_level ?? '—' }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @if ($device->animal)
                                    <a href="{{ route('animals.show', $device->animal) }}" class="text-xs text-indigo-600 hover:underline dark:text-indigo-400">{{ $device->animal->name }}</a>
                                @else
                                    <span class="text-xs text-slate-400">Belum terpasang</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400">
                                {{ $device->last_seen_at ? $device->last_seen_at->diffForHumans() : 'Belum pernah lapor' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('devices.show', $device) }}" class="tt-btn-secondary px-2.5 py-1.5 text-xs">Detail</a>
                                    <a href="{{ route('devices.edit', $device) }}" class="tt-btn-secondary px-2.5 py-1.5 text-xs">Edit</a>
                                    <button
                                        x-data
                                        x-on:click.prevent='window.Alpine.confirmModal(
                                            "Hapus perangkat?",
                                            "Perangkat {{ $device->name }} akan dihapus permanen.",
                                            () => $wire.delete("{{ $device->id }}")
                                        )'
                                        class="tt-btn-secondary px-2.5 py-1.5 text-xs text-red-600 hover:border-red-300 dark:text-red-400"
                                    >Hapus</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-400">
                                Tidak ada perangkat ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 p-4 dark:border-slate-700">
            {{ $devices->links() }}
        </div>
    </div>
</div>