<div>
    {{-- ===== Header + aksi ===== --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex h-4 w-4 rounded" style="background: {{ $fence->color }}"></span>
            <span class="tt-badge {{ $fence->fence_type === 'inclusion' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' }}">
                {{ $fence->fence_type === 'inclusion' ? 'Inclusion' : 'Exclusion' }}
            </span>
            <span class="tt-badge {{ $fence->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300' }}">
                {{ $fence->is_active ? 'Aktif' : 'Nonaktif' }}
            </span>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                wire:click="toggleActive"
                class="tt-btn-secondary"
            >
                {{ $fence->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
            </button>
            <a href="{{ route('fences.edit', $fence) }}" class="tt-btn-secondary">Edit Fence</a>
            <button
                x-data
                x-on:click.prevent='window.Alpine.confirmModal(
                    "Hapus Fence?",
                    "Fence {{ $fence->name }} beserta riwayat event akan dihapus permanen.",
                    () => {
                        if (window.ttFenceDetailDelete) {
                            window.ttFenceDetailDelete();
                        }
                    }
                )'
                class="tt-btn-secondary text-red-600 hover:border-red-300 dark:text-red-400"
            >Hapus</button>
        </div>
    </div>

    {{-- ===== Statistik ringkas ===== --}}
    <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        <div class="tt-card p-4">
            <div class="text-xs text-slate-400">Luas Zona</div>
            <div class="text-lg font-bold tabular-nums text-slate-800 dark:text-slate-100">{{ number_format((float) $fence->area_hectares, 4, ',', '.') }} ha</div>
        </div>
        <div class="tt-card p-4">
            <div class="text-xs text-slate-400">Revisi</div>
            <div class="text-lg font-bold tabular-nums text-slate-800 dark:text-slate-100">v{{ $fence->version }}</div>
        </div>
        <div class="tt-card p-4">
            <div class="text-xs text-slate-400">Hewan Dilindungi</div>
            <div class="text-lg font-bold tabular-nums text-slate-800 dark:text-slate-100">{{ count($animalsWithStatus) }}</div>
        </div>
        <div class="tt-card p-4">
            <div class="text-xs text-slate-400">Event Keluar (Pekan Ini)</div>
            <div class="text-lg font-bold tabular-nums text-slate-800 dark:text-slate-100">{{ count($weekEvents) }}</div>
        </div>
        <div class="tt-card p-4">
            <div class="text-xs text-slate-400">Peringatan Belum Ditindak</div>
            <div class="text-lg font-bold tabular-nums {{ $unacknowledged > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-800 dark:text-slate-100' }}">{{ $unacknowledged }}</div>
        </div>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-3">
        {{-- ===== Peta utama ===== --}}
        <div wire:ignore class="tt-card overflow-hidden p-0 lg:col-span-2">
            <div
                id="fence-detail-data"
                class="hidden"
                data-fence-name="{{ $fence->name }}"
                data-fence-color="{{ $fence->color }}"
                data-fence-active="{{ $fence->is_active ? '1' : '0' }}"
                data-polygon='@json($mapPolygon, JSON_HEX_APOS)'
                data-animals='@json($mapAnimals, JSON_HEX_APOS)'
            ></div>
            <div class="flex items-center justify-between border-b border-slate-200 p-3 dark:border-slate-700">
                <div class="flex items-center gap-2 text-sm">
                    <span class="inline-flex h-3 w-3 rounded-full bg-green-500"></span>
                    <span class="text-slate-500 dark:text-slate-400">Di dalam</span>
                    <span class="inline-flex h-3 w-3 rounded-full bg-red-500"></span>
                    <span class="text-slate-500 dark:text-slate-400">Di luar</span>
                    <span class="inline-flex h-3 w-3 rounded-full bg-slate-400"></span>
                    <span class="text-slate-500 dark:text-slate-400">Tanpa lokasi</span>
                </div>
                <span class="text-xs text-slate-400">{{ $fence->name }}</span>
            </div>
            <div id="fence-detail-map" class="h-[480px] w-full"></div>
        </div>

        {{-- ===== Panel: hewan diproteksi ===== --}}
        <div class="tt-card p-4">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-700 dark:text-slate-200">Hewan Dilindungi</h2>
                <a href="{{ route('fences.edit', $fence) }}" class="text-xs text-indigo-600 hover:underline dark:text-indigo-400">Kelola</a>
            </div>
            <div class="mt-3 space-y-2">
                @forelse ($animalsWithStatus as $item)
                    <div class="flex items-start gap-3 rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                        <span
                            class="mt-1 h-3 w-3 shrink-0 rounded-full {{ $item['status'] === 'inside' ? 'bg-green-500' : ($item['status'] === 'outside' ? 'bg-red-500' : 'bg-slate-400') }}"
                        ></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <span class="truncate text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $item['name'] }}</span>
                                <span class="text-xs text-slate-400">#{{ $item['tag_number'] }}</span>
                            </div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">
                                {{ $item['label'] }}
                                @if ($item['updated_at'])
                                    <span class="text-slate-400">· {{ $item['updated_at'] }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">Belum ada hewan yang dilindungi oleh fence ini.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        {{-- ===== Statistik pekanan keluar ===== --}}
        <div class="tt-card p-4">
            <h2 class="text-sm font-bold text-slate-700 dark:text-slate-200">Keluar per Hari (Pekan Ini)</h2>
            <div class="mt-3 space-y-1.5">
                @foreach ($weekExitsPerDay as $day => $count)
                    <div class="flex items-center gap-3">
                        <span class="w-24 shrink-0 text-xs text-slate-500 dark:text-slate-400">{{ $day }}</span>
                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                            <div
                                class="h-full rounded-full bg-red-500"
                                style="width: {{ $weekEvents->isNotEmpty() ? max(4, round($count / $weekEvents->count() * 100)) : 0 }}%"
                            ></div>
                        </div>
                        <span class="w-6 text-right text-xs font-semibold tabular-nums text-slate-600 dark:text-slate-300">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ===== Timeline event ===== --}}
        <div class="tt-card p-4">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-700 dark:text-slate-200">Riwayat Event</h2>
                <span class="text-xs text-slate-400">25 terakhir</span>
            </div>
            <div class="mt-3 max-h-80 space-y-2 overflow-y-auto pr-1">
                @forelse ($timeline as $event)
                    @php
                        $eventLabels = [
                            'exit' => 'Keluar dari fence',
                            'outside' => 'Berada di luar zona',
                            'enter' => 'Masuk zona larangan',
                            'inside' => 'Berada di dalam zona',
                        ];
                        $eventColors = [
                            'exit' => 'bg-red-500',
                            'outside' => 'bg-red-500',
                            'enter' => 'bg-amber-500',
                            'inside' => 'bg-green-500',
                        ];
                    @endphp
                    <div class="flex items-start gap-3 rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                        <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $eventColors[$event->event_type] ?? 'bg-slate-400' }}"></span>
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium text-slate-700 dark:text-slate-200">
                                {{ $eventLabels[$event->event_type] ?? ucfirst($event->event_type) }}
                            </div>
                            <div class="text-xs text-slate-400">
                                {{ $event->animal?->name ?? 'Hewan dihapus' }} · {{ $event->created_at?->diffForHumans() }}
                            </div>
                        </div>
                        @if ($event->is_alert && ! $event->is_acknowledged)
                            <span class="tt-badge bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">Belum ditindak</span>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-400">Belum ada event untuk fence ini.</p>
                @endforelse
            </div>
        </div>
    </div>

    @assets
        <script>
            function __ttInitFenceDetail() {
                (function () {
                const L = window.L;

                if (!L) {
                    return;
                }

                const mapEl = document.getElementById('fence-detail-map');
                const dataEl = document.getElementById('fence-detail-data');

                if (!mapEl || !dataEl) {
                    return;
                }

                let polygon = [];
                let animals = [];

                try {
                    polygon = JSON.parse(dataEl.dataset.polygon || '[]');
                } catch (e) {
                    polygon = [];
                }

                try {
                    animals = JSON.parse(dataEl.dataset.animals || '[]');
                } catch (e) {
                    animals = [];
                }

                const color = dataEl.dataset.fenceColor || '#22c55e';

                const map = L.map(mapEl, { zoomControl: true });

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                }).addTo(map);

                const layer = window.TT.fenceLayer(polygon, color);

                if (layer) {
                    layer.addTo(map);
                    map.fitBounds(layer.getBounds(), { padding: [30, 30] });
                } else {
                    map.setView([-7.5, 110.2], 12);
                }

                const withLocation = animals.filter((a) => a.lat !== null && a.lng !== null);

                withLocation.forEach((animal) => {
                    L.marker([animal.lat, animal.lng], {
                        icon: window.TT.animalStatusIcon(animal.status || 'none'),
                    })
                        .addTo(map)
                        .bindPopup(
                            '<strong>' + animal.name + '</strong><br><span>' +
                            (animal.label || '-') + '</span>'
                        );
                });

                if (withLocation.length > 0 && layer) {
                    map.fitBounds(layer.getBounds().pad(0.2).extend([
                        withLocation[0].lat,
                        withLocation[0].lng,
                    ]), { padding: [30, 30] });
                }

                const fenceId = {{ $fence->getKey() }};

                window.ttFenceDetailDelete = () => {
                    const root = document.getElementById('fence-detail-map')?.closest('[wire\\\\:id]') || null;
                    const c = root
                        ? window.Livewire.find(root.getAttribute('wire:id'))
                        : window.Livewire.first();
                    if (c) {
                        c.call('deleteFence');
                    }
                };
                window.ttFenceDetailMap = map;
                })();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', __ttInitFenceDetail);
            } else {
                __ttInitFenceDetail();
            }
        </script>
    @endassets
</div>