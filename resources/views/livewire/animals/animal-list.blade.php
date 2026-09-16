<div wire:poll.30s>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">Hewan Ternak</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Data lengkap hewan: identitas, pemasangan tracker, dan rekam kesehatan.
            </p>
        </div>
        <a href="{{ route('animals.create') }}" class="tt-btn-primary">
            + Tambah Hewan
        </a>
    </div>

    @if (session('status'))
        <div class="mt-4 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-700 dark:bg-green-900/40 dark:text-green-200">
            {{ session('status') }}
        </div>
    @endif

    <div class="tt-card mt-5 overflow-hidden p-0">
        <div class="flex flex-col gap-3 border-b border-slate-200 p-4 dark:border-slate-700 lg:flex-row lg:items-center lg:justify-between">
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari nama / tag..."
                class="tt-input lg:w-64"
            >
            <div class="flex flex-wrap gap-2 text-sm">
                <button
                    type="button"
                    wire:click="$set('speciesFilter', '')"
                    class="tt-badge cursor-pointer hover:opacity-80 @if($speciesFilter === '') bg-slate-600 text-white @endif"
                >Semua</button>
                @foreach (['sapi', 'kambing', 'domba', 'kerbau', 'lainnya'] as $species)
                    <button
                        type="button"
                        wire:click="$set('speciesFilter', '{{ $species }}')"
                        class="tt-badge cursor-pointer capitalize hover:opacity-80 @if($speciesFilter === $species) bg-slate-600 text-white @endif"
                    >{{ $species }}</button>
                @endforeach
                <button
                    type="button"
                    wire:click="$set('healthFilter', '{{ $healthFilter === 'sehat' ? '' : 'sehat' }}')"
                    class="tt-badge cursor-pointer hover:opacity-80 @if($healthFilter === 'sehat') bg-slate-600 text-white @endif"
                >Hanya Sehat</button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-100 text-xs uppercase text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Hewan</th>
                        <th class="px-4 py-3">Spesies</th>
                        <th class="px-4 py-3">Jenis Kelamin</th>
                        <th class="px-4 py-3">Berat</th>
                        <th class="px-4 py-3">Kesehatan</th>
                        <th class="px-4 py-3">Tracker</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse ($animals as $animal)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($animal->photo_path)
                                        <img src="{{ asset('storage/'.$animal->photo_path) }}" alt="{{ $animal->name }}" class="h-10 w-10 rounded-full object-cover">
                                    @else
                                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300">
                                            {{ strtoupper(substr($animal->name, 0, 1)) }}
                                        </span>
                                    @endif
                                    <div>
                                        <a href="{{ route('animals.show', $animal) }}" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">
                                            {{ $animal->name }}
                                        </a>
                                        <div class="text-xs text-slate-400">{{ $animal->tag_number }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 capitalize text-slate-600 dark:text-slate-300">{{ $animal->species }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                @if ($animal->gender === 'jantan')
                                    ♂ Jantan
                                @else
                                    ♀ Betina
                                @endif
                            </td>
                            <td class="px-4 py-3 tabular-nums text-slate-600 dark:text-slate-300">
                                {{ $animal->weight_kg ? number_format((float) $animal->weight_kg, 1) : '—' }} kg
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $healthMap = [
                                        'sehat' => ['bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300', 'Sehat'],
                                        'sakit' => ['bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300', 'Sakit'],
                                        'hamil' => ['bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300', 'Hamil'],
                                        'karantina' => ['bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300', 'Karantina'],
                                        'lainnya' => ['bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300', 'Lainnya'],
                                    ];
                                    $health = $healthMap[$animal->health_status] ?? $healthMap['lainnya'];
                                @endphp
                                <span class="{{ $health[0] }} tt-badge">{{ $health[1] }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($animal->device)
                                    <a href="{{ route('devices.show', $animal->device) }}" class="text-xs text-indigo-600 hover:underline dark:text-indigo-400">{{ $animal->device->name }}</a>
                                @else
                                    <span class="text-xs text-slate-400">Belum terpasang</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('animals.show', $animal) }}" class="tt-btn-secondary px-2.5 py-1.5 text-xs">Detail</a>
                                    <a href="{{ route('animals.edit', $animal) }}" class="tt-btn-secondary px-2.5 py-1.5 text-xs">Edit</a>
                                    <button
                                        x-data
                                        x-on:click.prevent='window.Alpine.confirmModal(
                                            "Hapus hewan?",
                                            "Hewan {{ $animal->name }} akan dihapus permanen.",
                                            () => $wire.delete("{{ $animal->id }}")
                                        )'
                                        class="tt-btn-secondary px-2.5 py-1.5 text-xs text-red-600 hover:border-red-300 dark:text-red-400"
                                    >Hapus</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-400">
                                Tidak ada hewan ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 p-4 dark:border-slate-700">
            {{ $animals->links() }}
        </div>
    </div>
</div>