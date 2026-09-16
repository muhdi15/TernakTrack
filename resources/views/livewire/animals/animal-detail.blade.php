<div x-data="{ tab: 'info' }">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('animals.index') }}" class="tt-btn-secondary">← Kembali</a>
            <div class="flex items-center gap-3">
                @if ($animal->photo_path)
                    <img src="{{ asset('storage/'.$animal->photo_path) }}" alt="{{ $animal->name }}" class="h-12 w-12 rounded-full object-cover">
                @else
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100 text-lg font-semibold text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300">
                        {{ strtoupper(substr($animal->name, 0, 1)) }}
                    </span>
                @endif
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ $animal->name }}</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ $animal->tag_number }} · {{ ucfirst($animal->species) }}</p>
                </div>
            </div>
        </div>
        <div class="flex gap-2">
            <button type="button" wire:click="locateNow" class="tt-btn-primary">📍 Lacak Sekarang</button>
            <a href="{{ route('animals.edit', $animal) }}" class="tt-btn-secondary">Edit</a>
        </div>
    </div>

    @if (session('status'))
        <div class="mt-4 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-700 dark:bg-green-900/40 dark:text-green-200">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mt-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-700 dark:bg-red-900/40 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    {{-- Tab bar --}}
    <div class="mt-5 flex gap-2 border-b border-slate-200 dark:border-slate-700">
        <button x-on:click="tab = 'info'" x-bind:class="tab === 'info' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-400'" class="border-b-2 px-4 py-2 text-sm font-medium">Info Umum</button>
        <button x-on:click="tab = 'movement'" x-bind:class="tab === 'movement' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-400'" class="border-b-2 px-4 py-2 text-sm font-medium">Pergerakan</button>
        <button x-on:click="tab = 'fences'" x-bind:class="tab === 'fences' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-400'" class="border-b-2 px-4 py-2 text-sm font-medium">Zona & Pagar</button>
        <button x-on:click="tab = 'health'" x-bind:class="tab === 'health' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-400'" class="border-b-2 px-4 py-2 text-sm font-medium">Rekam Kesehatan</button>
    </div>

    {{-- TAB: Info Umum --}}
    <section x-show="tab === 'info'" class="mt-5">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="tt-card">
                <p class="text-xs uppercase tracking-wide text-slate-400">Jenis Kelamin</p>
                <p class="mt-1 text-lg font-semibold">{{ $animal->gender === 'jantan' ? '♂ Jantan' : '♀ Betina' }}</p>
            </div>
            <div class="tt-card">
                <p class="text-xs uppercase tracking-wide text-slate-400">Berat</p>
                <p class="mt-1 text-lg font-semibold">{{ $animal->weight_kg ? number_format((float) $animal->weight_kg, 1).' kg' : '—' }}</p>
            </div>
            <div class="tt-card">
                <p class="text-xs uppercase tracking-wide text-slate-400">Tanggal Lahir</p>
                <p class="mt-1 text-lg font-semibold">{{ $animal->birth_date?->format('d M Y') ?: '—' }}</p>
            </div>
            <div class="tt-card">
                <p class="text-xs uppercase tracking-wide text-slate-400">Kesehatan</p>
                <p class="mt-1 text-lg font-semibold capitalize">{{ $animal->health_status }}</p>
            </div>
        </div>

        <div class="tt-card mt-5">
            <h2 class="mb-3 text-base font-semibold text-slate-800 dark:text-slate-100">Tracker Terpasang</h2>
            @if ($animal->device)
                <div class="flex items-center justify-between rounded-lg border border-slate-200 p-4 dark:border-slate-700">
                    <div>
                        <a href="{{ route('devices.show', $animal->device) }}" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">{{ $animal->device->name }}</a>
                        <div class="text-xs text-slate-400">{{ $animal->device->device_code }}</div>
                    </div>
                    <div class="text-right">
                        @if ($animal->device->isOnline())
                            <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">● ONLINE</span>
                        @else
                            <span class="text-xs font-semibold text-slate-400">○ OFFLINE</span>
                        @endif
                        <div class="mt-1 text-xs tabular-nums text-slate-400">Baterai {{ $animal->device->battery_level ?? '—' }}%</div>
                    </div>
                </div>
            @else
                <p class="text-sm text-slate-400">Belum ada tracker. Pilih perangkat lewat menu Edit.</p>
            @endif
        </div>

        @if ($animal->notes)
            <div class="tt-card mt-5">
                <h2 class="mb-2 text-base font-semibold text-slate-800 dark:text-slate-100">Catatan</h2>
                <p class="text-sm text-slate-600 dark:text-slate-300">{{ $animal->notes }}</p>
            </div>
        @endif
    </section>

    {{-- TAB: Pergerakan --}}
    <section x-show="tab === 'movement'" x-cloak class="mt-5">
        <div class="tt-card">
            <h2 class="mb-1 text-base font-semibold text-slate-800 dark:text-slate-100">Kecepatan & Jarak Tempuh per Hari (7 hari)</h2>
            <p class="mb-3 text-sm text-slate-500 dark:text-slate-400">Bar hijau = jarak tempuh (km), garis biru = kecepatan rata-rata (km/jam).</p>
            <div wire:ignore class="h-72">
                <canvas id="movement-chart"></canvas>
            </div>
        </div>

        <div class="tt-card mt-5">
            <h2 class="mb-3 text-base font-semibold text-slate-800 dark:text-slate-100">Peta Pergerakan 24 Jam Terakhir</h2>
            <p class="mb-3 text-sm text-slate-500 dark:text-slate-400">Warna segmen berubah mengikuti urutan waktu (merah → biru → hijau).</p>
            <div wire:ignore id="movement-map" class="h-96 rounded-lg"></div>
        </div>

        <div class="tt-card mt-5 overflow-hidden p-0">
            <h2 class="border-b border-slate-200 px-5 py-4 text-base font-semibold text-slate-800 dark:border-slate-700 dark:text-slate-100">Statistik Harian (7 hari terakhir)</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-100 text-xs uppercase text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                        <tr>
                            <th class="px-5 py-3">Tanggal</th>
                            <th class="px-5 py-3">Jarak (km)</th>
                            <th class="px-5 py-3">Kecepatan Rata-rata (km/jam)</th>
                            <th class="px-5 py-3">Jumlah Titik</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @foreach ($daily as $row)
                            <tr>
                                <td class="px-5 py-3">{{ $row['label'] }}</td>
                                <td class="px-5 py-3 tabular-nums">{{ $row['distance_km'] }}</td>
                                <td class="px-5 py-3 tabular-nums">{{ $row['avg_speed_kmh'] ?? '—' }}</td>
                                <td class="px-5 py-3 tabular-nums">{{ $row['points'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- TAB: Zona & Pagar --}}
    <section x-show="tab === 'fences'" x-cloak class="mt-5 grid gap-4 lg:grid-cols-2">
        <div class="tt-card">
            <h2 class="mb-3 text-base font-semibold text-slate-800 dark:text-slate-100">Pagar Virtual Terpasang</h2>
            @forelse ($animal->fences as $fence)
                <div class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3 dark:border-slate-700">
                    <div>
                        <div class="font-medium text-slate-700 dark:text-slate-200">{{ $fence->name }}</div>
                        <div class="text-xs text-slate-400">
                            {{ $fence->fence_type === 'inclusion' ? 'Zona Inklusi · di dalam = aman' : 'Zona Eksklusi · di dalam = terlarang' }}
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-400">Belum ada pagar virtual yang ditugaskan.</p>
            @endforelse
        </div>

        <div class="tt-card overflow-hidden p-0">
            <h2 class="border-b border-slate-200 px-5 py-4 text-base font-semibold text-slate-800 dark:border-slate-700 dark:text-slate-100">
                Riwayat Kejadian Zona ({{ $events->count() }})
            </h2>
            <div class="max-h-96 divide-y divide-slate-200 overflow-y-auto dark:divide-slate-700">
                @forelse ($events as $event)
                    @php
                        $evt = match ($event->event_type) {
                            'exit' => ['bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300', 'Keluar zona'],
                            'enter' => ['bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300', 'Masuk zone'],
                            'inside' => ['bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300', 'Di dalam zona'],
                            'outside' => ['bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300', 'Di luar zona'],
                            default => ['bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300', $event->event_type],
                        };
                    @endphp
                    <div class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="{{ $evt[0] }} tt-badge shrink-0">{{ $evt[1] }}</span>
                                @if ($event->fence)
                                    <span class="truncate text-slate-500 dark:text-slate-400">{{ $event->fence->name }}</span>
                                @endif
                            </div>
                            <div class="mt-1 text-xs text-slate-400">{{ $event->created_at->format('d M Y H:i') }}</div>
                        </div>
                        @if ($event->distance_from_fence_meters !== null)
                            <span class="shrink-0 text-xs tabular-nums text-slate-400">{{ number_format((float) $event->distance_from_fence_meters, 1) }} m</span>
                        @endif
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-slate-400">Belum ada kejadian zona tercatat.</p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- TAB: Rekam Kesehatan --}}
    <section x-show="tab === 'health'" x-cloak class="mt-5">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-semibold text-slate-800 dark:text-slate-100">Riwayat Kesehatan</h2>
            <button type="button" x-on:click="$dispatch('create-health-record')" class="tt-btn-primary">+ Tambah Rekam Medis</button>
        </div>

        <div class="tt-card mt-4 overflow-hidden p-0">
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                @forelse ($healthRecords as $record)
                    <div class="flex items-center justify-between gap-3 px-5 py-4 text-sm">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-slate-700 dark:text-slate-200">
                                    {{ ucfirst(str_replace('_', ' ', $record->type)) }}
                                </span>
                                <span class="text-xs text-slate-400">{{ $record->record_date->format('d M Y') }}</span>
                            </div>
                            <p class="mt-0.5 text-slate-500 dark:text-slate-400">{{ $record->description }}</p>
                            <div class="mt-1 text-xs text-slate-400">
                                @if ($record->vet_name)
                                    Dokter: {{ $record->vet_name }} ·
                                @endif
                                @if ($record->next_due_date)
                                    Tenggat berikutnya: {{ $record->next_due_date->format('d M Y') }}
                                @endif
                            </div>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <button
                                type="button"
                                x-on:click="$dispatch('edit-health-record', { recordId: {{ $record->id }} })"
                                class="tt-btn-secondary px-2.5 py-1.5 text-xs"
                            >Edit</button>
                            <button
                                type="button"
                                x-data
                                x-on:click.prevent="window.Alpine.confirmModal(
                                    'Hapus rekam medis?',
                                    'Rekam medis ini akan dihapus permanen.',
                                    () => $wire.deleteHealthRecord('{{ $record->id }}')
                                )"
                                class="tt-btn-secondary px-2.5 py-1.5 text-xs text-red-600 hover:border-red-300 dark:text-red-400"
                            >Hapus</button>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-slate-400">Belum ada catatan kesehatan.</p>
                @endforelse
            </div>
        </div>

        <livewire:animals.health-record-form :animal="$animal" :key="'health-form-'.$animal->id" />
    </section>

    @assets
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.TT.movementChart(
                document.getElementById('movement-chart'),
                {!! json_encode($movementLabels) !!},
                {!! json_encode($distanceKm) !!},
                {!! json_encode($avgSpeedKmh) !!},
            );

            window.TT.movementMap('movement-map', {!! json_encode($mapPoints) !!});
        });
    </script>
    @endassets
</div>