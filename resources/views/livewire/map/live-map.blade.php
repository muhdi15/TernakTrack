<div wire:poll.10s>
    {{-- ===== Filter bar ===== --}}
    <div class="tt-card flex flex-col gap-3 p-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            @if ($farms->isNotEmpty())
                <select wire:model.live="farmId" class="tt-input !w-auto text-sm">
                    <option value="">Semua Lokasi</option>
                    @foreach ($farms as $farm)
                        <option value="{{ $farm->id }}">{{ $farm->name }}</option>
                    @endforeach
                </select>
            @endif

            <div class="flex items-center gap-1.5 text-sm">
                <button
                    type="button"
                    onclick="window.ttLiveMap?.setStatusFilter('')"
                    class="tt-badge cursor-pointer hover:opacity-80 bg-slate-600 text-white"
                >Status: Semua</button>
                <button
                    type="button"
                    onclick="window.ttLiveMap?.setStatusFilter('inside')"
                    class="tt-badge cursor-pointer hover:opacity-80 bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300"
                >Di Dalam</button>
                <button
                    type="button"
                    onclick="window.ttLiveMap?.setStatusFilter('outside')"
                    class="tt-badge cursor-pointer hover:opacity-80 bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300"
                >Di Luar</button>
                <button
                    type="button"
                    onclick="window.ttLiveMap?.setStatusFilter('unknown')"
                    class="tt-badge cursor-pointer hover:opacity-80 bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300"
                >Tanpa Lokasi</button>
            </div>

            @if (count($speciesOptions) > 0)
                <div class="flex items-center gap-1.5 text-sm">
                    <button
                        type="button"
                        wire:click="$set('species', '')"
                        class="tt-badge cursor-pointer hover:opacity-80 @if($species === '') bg-slate-600 text-white @else bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300 @endif"
                    >Semua Spesies</button>
                    @foreach ($speciesOptions as $option)
                        <button
                            type="button"
                            wire:click="$set('species', '{{ $option }}')"
                            class="tt-badge cursor-pointer capitalize hover:opacity-80 @if($species === $option) bg-slate-600 text-white @else bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300 @endif"
                        >{{ $option }}</button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2 text-sm">
                <label for="trail-animal" class="whitespace-nowrap text-xs text-slate-500 dark:text-slate-400">Lacak:</label>
                <select id="trail-animal" wire:model.live="trailAnimalId" class="tt-input !w-auto text-sm">
                    <option value="">— Pilih hewan —</option>
                    @foreach ($animals as $animal)
                        <option value="{{ $animal->id }}">{{ $animal->name }} ({{ $animal->tag_number }})</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2 text-sm">
                <label for="trail-hours" class="whitespace-nowrap text-xs text-slate-500 dark:text-slate-400">Riwayat:</label>
                <input
                    id="trail-hours"
                    type="range"
                    min="1"
                    max="7"
                    step="1"
                    wire:model.live="trailHours"
                    class="accent-green-600"
                    title="{{ $trailHours }} hari terakhir"
                >
                <span class="w-8 text-xs font-semibold tabular-nums text-slate-600 dark:text-slate-300">{{ $trailHours }}h</span>
            </div>
        </div>
    </div>

    {{-- ===== Peta utama ===== --}}
    <div class="tt-card relative isolate mt-4 overflow-hidden p-0">
        <div wire:ignore>
            <div id="live-map" class="h-[70vh] w-full"></div>
        </div>

        <div class="absolute start-top inset-x-0 top-0 z-[490] flex flex-wrap items-center gap-1.5 border-b border-slate-200 bg-white/90 px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-900/90">
            <span class="inline-flex h-3 w-3 rounded-full bg-green-500"></span>
            <span class="text-slate-500 dark:text-slate-400">Di dalam</span>
            <span class="inline-flex h-3 w-3 rounded-full bg-red-500"></span>
            <span class="text-slate-500 dark:text-slate-400">Di luar</span>
            <span class="inline-flex h-3 w-3 rounded-full bg-slate-400"></span>
            <span class="text-slate-500 dark:text-slate-400">Tanpa lokasi</span>
            <span class="mx-2 h-4 w-px bg-slate-300 dark:bg-slate-600"></span>
            @foreach ($mapPayload['fences'] as $fenceItem)
                <label class="flex cursor-pointer items-center gap-1.5">
                    <input
                        type="checkbox"
                        id="fence-cb-{{ $fenceItem['id'] }}"
                        checked
                        onchange="window.ttLiveMap?.toggleFence({{ $fenceItem['id'] }}, this.checked)"
                        class="accent-green-600"
                    >
                    <span class="inline-flex h-2 w-2 rounded-full" style="background: {{ $fenceItem['color'] }}"></span>
                    <span class="max-w-24 truncate text-slate-600 dark:text-slate-300">{{ $fenceItem['name'] }}</span>
                </label>
            @endforeach
        </div>

        {{-- Panel info hewan (klik marker) --}}
        <div
            x-data="{
                open: false,
                info: null,
                close() { this.open = false; },
                track() {
                    if (this.info) { $wire.set('trailAnimalId', this.info.id); }
                    this.open = false;
                },
                openDetail() {
                    if (this.info) { window.location.href = '/animals/' + this.info.id; }
                },
            }"
            @tt.select-animal.window="info = $event.detail; open = true"
            x-cloak
            x-show="open"
            class="absolute end-3 top-14 z-[500] w-72 rounded-2xl bg-white p-4 shadow-xl ring-1 ring-slate-200 dark:bg-slate-800 dark:ring-slate-700"
        >
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <h3 class="truncate text-base font-bold text-slate-800 dark:text-slate-100" x-text="info?.name || ''"></h3>
                    <p class="text-xs text-slate-400 capitalize" x-text="(info?.species || '') + ' · #' + (info?.tag_number || '-')"></p>
                </div>
                <button @click="close()" class="text-slate-400 transition hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="mt-3 space-y-2 text-sm">
                <div class="flex items-center gap-2">
                    <span x-show="info?.status === 'inside'" class="inline-flex h-3 w-3 rounded-full bg-green-500"></span>
                    <span x-show="info?.status === 'outside'" class="inline-flex h-3 w-3 rounded-full bg-red-500"></span>
                    <span x-show="info?.status !== 'inside' && info?.status !== 'outside'" class="inline-flex h-3 w-3 rounded-full bg-slate-400"></span>
                    <span x-text="info?.label || '-'" class="text-slate-600 dark:text-slate-300"></span>
                </div>
                <div class="text-xs text-slate-400">
                    Pembaruan lokasi: <span x-text="info?.updated_at || 'belum ada'"></span>
                </div>
                <div class="text-xs">
                    <span class="font-medium text-slate-500 dark:text-slate-400">Tracker:</span>
                    <span
                        class="tt-badge ml-1"
                        x-text="info?.device_online ? 'Online' : 'Offline'"
                        x-bind:class="info?.device_online ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300'"
                    ></span>
                </div>
            </div>

            <div class="mt-4 flex gap-2">
                <button @click="track()" class="tt-btn-primary flex-1 !py-1.5 text-xs">Lacak Pergerakan</button>
                <button @click="openDetail()" class="tt-btn-secondary flex-1 !py-1.5 text-xs">Buka Detail</button>
            </div>
        </div>
    </div>

    <p class="mt-2 text-center text-xs text-slate-400">
        Peta diperbarui otomatis setiap 10 detik.
    </p>

    <script type="application/json" id="live-map-data" class="hidden">@json($mapPayload)</script>

    @assets
        <script>
            function __ttInitLiveMap() {
                (function () {
                const L = window.L;

                if (!L) {
                    return;
                }

                const mapEl = document.getElementById('live-map');

                if (!mapEl) {
                    return;
                }

                if (mapEl.dataset.inited) {
                    return;
                }

                mapEl.dataset.inited = '1';

                const state = {
                    map: null,
                    fenceLayerById: {},
                    animalMarkerById: {},
                    trailLayer: null,
                    statusFilter: '',
                    payload: { fences: [], animals: [], trail: [] },
                };

                function readPayload() {
                    const el = document.getElementById('live-map-data');
                    if (!el) {
                        return null;
                    }
                    try {
                        return JSON.parse(el.textContent || 'null');
                    } catch (e) {
                        return null;
                    }
                }

                function restyleFence(fence) {
                    const style = {
                        color: fence.color,
                        weight: fence.is_active ? 2 : 1,
                        dashArray: fence.is_active ? null : '4 6',
                        opacity: fence.is_active ? 1 : 0.55,
                        fillColor: fence.color,
                        fillOpacity: 0.15,
                    };
                    const layer = state.fenceLayerById[fence.id];
                    if (layer) {
                        layer.setStyle(style);
                    }
                    return style;
                }

                function applyFences(fences) {
                    const ids = new Set(fences.map((f) => f.id));

                    Object.keys(state.fenceLayerById).forEach((id) => {
                        if (!ids.has(Number(id))) {
                            state.map.removeLayer(state.fenceLayerById[id]);
                            delete state.fenceLayerById[id];
                        }
                    });

                    fences.forEach((fence) => {
                        let layer = state.fenceLayerById[fence.id];

                        if (!layer) {
                            layer = window.TT.fenceLayer(fence.polygon, fence.color);
                            if (!layer) {
                                return;
                            }
                            layer.bindPopup(
                                '<strong>' + fence.name + '</strong><br>' +
                                (fence.fence_type === 'inclusion' ? 'Zona Inclusion' : 'Zona Exclusion') +
                                ' · ' + String(fence.area_hectares) + ' ha'
                            );
                            state.fenceLayerById[fence.id] = layer;
                            layer.addTo(state.map);
                        }

                        restyleFence(fence);

                        const cb = document.getElementById('fence-cb-' + fence.id);
                        if (cb && !cb.checked) {
                            state.map.removeLayer(layer);
                            state.fenceLayerById[fence.id]._hidden = true;
                        } else if (cb && cb.checked && state.fenceLayerById[fence.id]._hidden) {
                            layer.addTo(state.map);
                            state.fenceLayerById[fence.id]._hidden = false;
                        }
                    });
                }

                function applyAnimals(animals) {
                    const ids = new Set(animals.map((a) => a.id));

                    Object.keys(state.animalMarkerById).forEach((id) => {
                        if (!ids.has(Number(id))) {
                            state.map.removeLayer(state.animalMarkerById[id]);
                            delete state.animalMarkerById[id];
                        }
                    });

                    animals.forEach((animal) => {
                        if (animal.lat === null || animal.lng === null) {
                            return;
                        }

                        let marker = state.animalMarkerById[animal.id];

                        if (!marker) {
                            marker = L.marker([animal.lat, animal.lng], {
                                icon: window.TT.animalStatusIcon(animal.status || 'none'),
                            });
                            state.animalMarkerById[animal.id] = marker;
                            marker.addTo(state.map);

                            marker.on('click', () => {
                                const info = {
                                    id: animal.id,
                                    name: animal.name,
                                    species: animal.species,
                                    tag_number: animal.tag_number,
                                    status: animal.status,
                                    label: animal.label,
                                    updated_at: animal.updated_at,
                                    device_online: !!animal.device_online,
                                };
                                window.dispatchEvent(new CustomEvent('tt.select-animal', { detail: info }));
                            });
                        } else {
                            marker.setLatLng([animal.lat, animal.lng]);
                            marker.setIcon(window.TT.animalStatusIcon(animal.status || 'none'));
                        }

                        const show = state.statusFilter === ''
                            || animal.status === state.statusFilter;

                        if (state.map.hasLayer(marker) && !show) {
                            state.map.removeLayer(marker);
                            marker._filtered = true;
                        } else if (!state.map.hasLayer(marker) && show && marker._filtered) {
                            marker.addTo(state.map);
                            marker._filtered = false;
                        }
                    });
                }

                function applyTrail(trail, animalId) {
                    if (state.trailLayer) {
                        state.map.removeLayer(state.trailLayer);
                        state.trailLayer = null;
                    }

                    if (animalId && Array.isArray(trail) && trail.length > 1) {
                        state.trailLayer = L.polyline(
                            trail.map((p) => [p.lat, p.lng]),
                            { color: '#0ea5e9', weight: 4, opacity: 0.85 }
                        ).addTo(state.map);

                        const bounds = state.trailLayer.getBounds();
                        const fenceBounds = Object.values(state.fenceLayerById)
                            .filter((l) => state.map.hasLayer(l) && !l._hidden)
                            .map((l) => l.getBounds());

                        if (fenceBounds.length > 0) {
                            let combined = fenceBounds[0];
                            fenceBounds.slice(1).forEach((b) => { combined = combined.extend(b); });
                            state.map.fitBounds(bounds.extend(combined), { padding: [40, 40] });
                        } else {
                            state.map.fitBounds(bounds, { padding: [40, 40] });
                        }
                    }
                }

                function refresh() {
                    const payload = readPayload();

                    if (!payload) {
                        return;
                    }

                    state.payload = payload;

                    applyFences(payload.fences || []);
                    applyAnimals(payload.animals || []);
                    applyTrail(payload.trail || [], payload.trailAnimalId || null);
                }

                // ---- Bootstrap ----
                state.map = L.map(mapEl);
                L.control.zoom({ position: 'topleft' }).addTo(state.map);
                state.map.setView([-7.5, 110.2], 12);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                }).addTo(state.map);

                state.payload = readPayload() || { fences: [], animals: [], trail: [] };

                if (state.payload.fences.length > 0 && state.payload.fences[0].polygon.length >= 3) {
                    /* potongan bounds: render dulu lalu fit */
                }

                refresh();

                const fenceBounds = Object.values(state.fenceLayerById)
                    .filter((l) => state.map.hasLayer(l) && !l._hidden)
                    .map((l) => l.getBounds());

                if (fenceBounds.length > 0) {
                    let combined = fenceBounds[0];
                    fenceBounds.slice(1).forEach((b) => { combined = combined.extend(b); });
                    state.map.fitBounds(combined, { padding: [40, 40] });
                }

                window.ttLiveMap = {
                    map: state.map,
                    setStatusFilter(status) {
                        state.statusFilter = status;
                        state.payload.animals.forEach((animal) => {
                            const marker = state.animalMarkerById[animal.id];
                            if (!marker) {
                                return;
                            }
                            const show = status === '' || animal.status === status;
                            if (state.map.hasLayer(marker) && !show) {
                                state.map.removeLayer(marker);
                                marker._filtered = true;
                            } else if (!state.map.hasLayer(marker) && show) {
                                marker.addTo(state.map);
                                marker._filtered = false;
                            }
                        });
                    },
                    toggleFence(id, visible) {
                        const layer = state.fenceLayerById[id];
                        if (!layer) {
                            return;
                        }
                        if (visible && !state.map.hasLayer(layer)) {
                            layer.addTo(state.map);
                            layer._hidden = false;
                        } else if (!visible && state.map.hasLayer(layer)) {
                            state.map.removeLayer(layer);
                            layer._hidden = true;
                        }
                    },
                    refresh,
                };

                document.addEventListener('livewire:init', () => {
                    Livewire.hook('commit', ({ succeed }) => {
                        succeed(() => refresh());
                    });
                });
                })();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', __ttInitLiveMap);
            } else {
                __ttInitLiveMap();
            }
        </script>
    @endassets
</div>