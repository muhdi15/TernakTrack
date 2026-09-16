<div>
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ $fence ? 'Perbarui zona virtual "'.$fence->name.'".' : 'Gambar polygon pada peta, lalu tetapkan hewan yang dilindungi.' }}
            </p>
        </div>
        <a href="{{ route('fences.index') }}" class="tt-btn-secondary">← Kembali</a>
    </div>

    <div class="mt-5 grid gap-6 lg:grid-cols-5">
        {{-- ===== Kolom Form ===== --}}
        <form wire:submit="save" class="tt-card space-y-5 p-5 lg:col-span-2">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="tt-label" for="fence-name">Nama Fence</label>
                    <input
                        id="fence-name"
                        type="text"
                        wire:model.live="name"
                        class="tt-input"
                        placeholder="cth. Kandang Utara"
                    >
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="tt-label" for="fence-color">Warna Zona</label>
                    <div class="flex items-center gap-2">
                        <input
                            id="fence-color"
                            type="color"
                            x-on:change="window.ttFenceFormMap?.setColor($el.value)"
                            wire:model.live="color"
                            class="h-10 w-14 cursor-pointer rounded-lg border border-slate-300 bg-white p-1 dark:border-slate-600 dark:bg-slate-800"
                        >
                        <code class="text-xs text-slate-500 dark:text-slate-400">{{ $color }}</code>
                    </div>
                    @error('color') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="tt-label">Metode Zona</label>
                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                    <label class="flex items-start gap-2 rounded-lg border border-slate-200 p-3 text-sm hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800/50">
                        <input type="radio" wire:model.live="fenceType" value="inclusion" class="mt-0.5 accent-green-600">
                        <span>
                            <span class="font-medium text-slate-700 dark:text-slate-200">Inclusion</span>
                            <span class="block text-xs text-slate-400">Hewan harus <b>di dalam</b> zona agar aman.</span>
                        </span>
                    </label>
                    <label class="flex items-start gap-2 rounded-lg border border-slate-200 p-3 text-sm hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800/50">
                        <input type="radio" wire:model.live="fenceType" value="exclusion" class="mt-0.5 accent-amber-600">
                        <span>
                            <span class="font-medium text-slate-700 dark:text-slate-200">Exclusion</span>
                            <span class="block text-xs text-slate-400">Hewan <b>tidak boleh</b> masuk zona larangan.</span>
                        </span>
                    </label>
                </div>
                @error('fenceType') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="tt-label" for="fence-farm">Lokasi (Opsional)</label>
                <select id="fence-farm" wire:model.live="farmId" class="tt-input">
                    <option value="">— Tanpa lokasi —</option>
                    @foreach ($farms as $farm)
                        <option value="{{ $farm->id }}">{{ $farm->name }}</option>
                    @endforeach
                </select>
                @error('farmId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="tt-label" for="fence-desc">Deskripsi</label>
                <textarea
                    id="fence-desc"
                    rows="2"
                    wire:model.live="description"
                    class="tt-input"
                    placeholder="Opsional: keterangan zona, hewan target, dll."
                ></textarea>
                @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-2 sm:grid-cols-2">
                <label class="flex items-start gap-2 rounded-lg border border-slate-200 p-3 text-sm hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800/50">
                    <input type="checkbox" wire:model.live="alertOnExit" class="mt-0.5 accent-green-600">
                    <span>
                        <span class="font-medium text-slate-700 dark:text-slate-200">Alert keluar</span>
                        <span class="block text-xs text-slate-400">Notifikasi saat hewan keluar zona.</span>
                    </span>
                </label>
                <label class="flex items-start gap-2 rounded-lg border border-slate-200 p-3 text-sm hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800/50">
                    <input type="checkbox" wire:model.live="alertOnEnter" class="mt-0.5 accent-green-600">
                    <span>
                        <span class="font-medium text-slate-700 dark:text-slate-200">Alert masuk</span>
                        <span class="block text-xs text-slate-400">Notifikasi saat hewan masuk zona larangan.</span>
                    </span>
                </label>
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <label class="tt-label !mb-0">Hewan Dilindungi ({{ count($selectedAnimals) }} dipilih)</label>
                    <div class="flex gap-2 text-xs">
                        <button type="button" wire:click="selectAllAnimals" class="text-indigo-600 hover:underline dark:text-indigo-400">Pilih Semua</button>
                        <button type="button" wire:click="clearAnimals" class="text-slate-500 hover:underline dark:text-slate-400">Bersihkan</button>
                    </div>
                </div>
                <div class="mt-2 grid max-h-64 gap-2 overflow-y-auto pr-1 sm:grid-cols-2">
                    @forelse ($animals as $animal)
                        <label class="flex items-start gap-2 rounded-lg border border-slate-200 p-2.5 text-sm hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800/50">
                            <input
                                type="checkbox"
                                wire:model.live="selectedAnimals"
                                value="{{ $animal->id }}"
                                {{ in_array($animal->id, $selectedAnimals, true) ? 'checked' : '' }}
                                class="mt-0.5 accent-green-600"
                            >
                            <span class="min-w-0">
                                <span class="block truncate font-medium text-slate-700 dark:text-slate-200">{{ $animal->name }}</span>
                                <span class="block truncate text-xs text-slate-400">{{ $animal->tag_number }}</span>
                            </span>
                        </label>
                    @empty
                        <p class="text-sm text-slate-400 sm:col-span-2">Belum ada hewan. Tambahkan hewan lebih dulu di modul Hewan.</p>
                    @endforelse
                </div>
                @error('selectedAnimals') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3 border-t border-slate-200 pt-5 dark:border-slate-700">
                <button type="submit" class="tt-btn-primary">
                    {{ $fence ? 'Simpan Perubahan' : 'Simpan Fence' }}
                </button>
                <a href="{{ route('fences.index') }}" class="tt-btn-secondary">Batal</a>
            </div>
        </form>

        {{-- ===== Kolom Peta ===== --}}
        <div class="space-y-3 lg:col-span-3">
            <div wire:ignore class="tt-card overflow-hidden p-0">
                <div
                    id="fence-initial-data"
                    class="hidden"
                    data-points='@json($initialPoints, JSON_HEX_APOS)'
                    data-center='@json($defaultCenter, JSON_HEX_APOS)'
                    data-color="{{ $color }}"
                ></div>
                <div id="fence-toolbar" class="flex flex-wrap items-center gap-2 border-b border-slate-200 p-3 dark:border-slate-700">
                    <button type="button" id="tb-draw" class="tt-btn-secondary px-3 py-1.5 text-xs">Gambar Polygon</button>
                    <button type="button" id="tb-finish" class="tt-btn-secondary px-3 py-1.5 text-xs" disabled>Selesai</button>
                    <button type="button" id="tb-edit" class="tt-btn-secondary px-3 py-1.5 text-xs" disabled>Edit Titik</button>
                    <button type="button" id="tb-clear" class="tt-btn-secondary px-3 py-1.5 text-xs text-red-600 hover:border-red-300 dark:text-red-400">Hapus Semua</button>
                    <span id="tb-hint" class="ml-auto text-xs text-slate-400">
                        Klik pada peta untuk menambah titik polygon.
                    </span>
                </div>
                <div id="fence-draw-map" class="h-[420px] w-full"></div>
            </div>

            {{-- Status luas & titik (di-render ulang oleh Livewire tiap sync) --}}
            <div class="tt-card flex flex-wrap items-center gap-4 p-4">
                <div>
                    <div class="text-xs text-slate-400">Titik Polygon</div>
                    <div id="fence-live-points" class="text-lg font-bold tabular-nums text-slate-800 dark:text-slate-100">
                        {{ count($initialPoints) }}
                    </div>
                </div>
                <div>
                    <div class="text-xs text-slate-400">Luas Zona</div>
                    <div id="fence-live-area" class="text-lg font-bold tabular-nums text-slate-800 dark:text-slate-100">
                        {{ $displayArea !== null ? number_format($displayArea, 4, ',', '.') : '—' }} ha
                    </div>
                </div>
                <div class="min-w-0 flex-1">
                    <a
                        href="{{ route('calibration.index') }}"
                        title="Fitur kalibrasi GPS tersedia pada sesi khusus kalibrasi."
                        class="tt-btn-secondary !w-auto px-3 py-1.5 text-xs"
                    >
                        Buat Polygon via GPS HP 📱
                    </a>
                </div>
                @error('polygon')
                    <div class="w-full rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-xs text-red-700 dark:border-red-700 dark:bg-red-900/40 dark:text-red-200">
                        {{ $message }}
                    </div>
                @enderror
            </div>
        </div>
    </div>

    @assets
        <script>
            function __ttInitFenceForm() {
                (function () {
                const L = window.L;

                if (!L) {
                    return;
                }

                const mapEl = document.getElementById('fence-draw-map');
                const initialData = document.getElementById('fence-initial-data');
                const toolbar = document.getElementById('fence-toolbar');

                if (!mapEl || !initialData) {
                    return;
                }

                let initPoints = [];
                try {
                    initPoints = JSON.parse(initialData.dataset.points || '[]');
                } catch (e) {
                    initPoints = [];
                }

                let center = { lat: -7.5, lng: 110.2 };
                try {
                    center = JSON.parse(initialData.dataset.center || 'null') || center;
                } catch (e) {
                    // pakai default
                }

                const state = {
                    color: initialData.dataset.color || '#22c55e',
                    polygon: null,
                    featureGroup: null,
                    markerLayer: null,
                    editMarkers: [],
                    editing: false,
                    drawing: false,
                    drawHandler: null,
                };

                function polygonStyle(color) {
                    return {
                        color,
                        weight: 2,
                        fillColor: color,
                        fillOpacity: 0.2,
                    };
                }

                function ringLatLngs() {
                    if (!state.polygon) {
                        return [];
                    }
                    return state.polygon.getLatLngs()[0] || [];
                }

                function vertexIcon(color) {
                    return L.divIcon({
                        className: '',
                        html: '<span style="display:inline-block;width:16px;height:16px;border-radius:9999px;background:' + color + ';border:2px solid #ffffff;box-shadow:0 1px 3px rgba(0,0,0,0.35);"></span>',
                        iconSize: [16, 16],
                        iconAnchor: [8, 8],
                    });
                }

                function applyRing(latlngs) {
                    if (!state.polygon) {
                        return;
                    }
                    state.polygon.setLatLngs([latlngs]);
                }

                function renderEditMarkers() {
                    state.markerLayer.clearLayers();
                    state.editMarkers = [];

                    ringLatLngs().forEach((ll, i) => {
                        const marker = L.marker(ll, {
                            draggable: true,
                            icon: vertexIcon(state.color),
                        }).addTo(state.markerLayer);

                        marker.on('click', () => removeVertex(i));
                        marker.on('drag', () => {
                            const lls = state.editMarkers.map((item) => item.marker.getLatLng());
                            applyRing(lls);
                        });
                        marker.on('dragend', () => {
                            renderEditMarkers();
                            sync();
                        });

                        state.editMarkers.push({ marker, index: i });
                    });
                }

                function clearEditMarkers() {
                    state.markerLayer.clearLayers();
                    state.editMarkers = [];
                }

                function enableEdit() {
                    if (state.drawing || !state.polygon) {
                        return;
                    }
                    cancelDrawing();
                    state.editing = true;
                    renderEditMarkers();
                    updateToolbar();
                }

                function disableEdit() {
                    state.editing = false;
                    clearEditMarkers();
                    updateToolbar();
                }

                function toggleEdit() {
                    if (state.editing) {
                        disableEdit();
                    } else {
                        enableEdit();
                    }
                }

                function removeVertex(index) {
                    if (!state.editing) {
                        return;
                    }
                    const lls = ringLatLngs();
                    if (lls.length <= 3) {
                        return;
                    }
                    lls.splice(index, 1);
                    applyRing(lls);
                    renderEditMarkers();
                    sync();
                }

                function startDrawing() {
                    cancelDrawing();
                    disableEdit();
                    state.drawing = true;
                    state.drawHandler = new L.Draw.Polygon(state.map, {
                        showArea: false,
                        allowIntersection: false,
                        shapeOptions: polygonStyle(state.color),
                    });
                    state.map.on('draw:drawstart', () => hint('Klik pada peta untuk menambah titik. Selesai dengan klik ganda atau tombol "Selesai".'));
                    state.map.on('draw:drawstop', () => hint('Mode menggambar dimatikan.'));
                    state.drawHandler.enable();
                    updateToolbar();
                    hint('Klik pada peta untuk menambah titik. Selesai dengan klik ganda atau tombol "Selesai".');
                }

                function cancelDrawing() {
                    if (state.drawHandler) {
                        state.drawHandler.disable();
                        state.drawHandler = null;
                    }
                    state.drawing = false;
                    state.map.off('draw:drawstart');
                    state.map.off('draw:drawstop');
                }

                function finishDrawing() {
                    if (!state.drawing || !state.drawHandler) {
                        return;
                    }
                    // L.Draw.Polygon menyelesaikan bentuk saat dblclick.
                    state.map.fire('dblclick');
                }

                function clearAll() {
                    cancelDrawing();
                    disableEdit();
                    if (state.polygon) {
                        state.featureGroup.removeLayer(state.polygon);
                        state.polygon = null;
                    }
                    sync();
                    hint('Polygon dihapus. Klik "Gambar Polygon" untuk mulai lagi.');
                }

                function setColor(color) {
                    const valid = /^#[0-9a-f]{6}$/i;
                    if (!valid.test(color)) {
                        return;
                    }
                    state.color = color;
                    if (state.polygon) {
                        state.polygon.setStyle(polygonStyle(color));
                    }
                    if (state.editing) {
                        renderEditMarkers();
                    }
                }

                function hint(text) {
                    const el = document.getElementById('tb-hint');
                    if (el) {
                        el.textContent = text;
                    }
                }

                function updateToolbar() {
                    const drawBtn = document.getElementById('tb-draw');
                    const finishBtn = document.getElementById('tb-finish');
                    const editBtn = document.getElementById('tb-edit');

                    if (!drawBtn || !finishBtn || !editBtn) {
                        return;
                    }

                    drawBtn.textContent = state.drawing ? 'Stop' : 'Gambar Polygon';
                    finishBtn.disabled = !state.drawing;
                    editBtn.disabled = state.drawing || !state.polygon;
                }

                function formComponent() {
                    const el = document.getElementById('fence-name')?.closest('[wire\\\\:id]') || null;
                    if (!el) {
                        return window.Livewire ? window.Livewire.first() : null;
                    }
                    return window.Livewire.find(el.getAttribute('wire:id'));
                }

                function sync() {
                    const pts = ringLatLngs().map((ll) => ({ lat: ll.lat, lng: ll.lng }));

                    if (window.__ttFenceInited && window.Livewire) {
                        const comp = formComponent();
                        if (comp) {
                            comp.set('polygon', pts.length >= 3 ? JSON.stringify(pts) : '[]');
                        }
                    }
                }

                function setPolygon(points) {
                    cancelDrawing();
                    disableEdit();

                    if (state.polygon) {
                        state.featureGroup.removeLayer(state.polygon);
                        state.polygon = null;
                    }

                    const lls = (points || []).map((p) => [p.lat, p.lng]);

                    if (lls.length >= 3) {
                        state.polygon = L.polygon(lls, polygonStyle(state.color)).addTo(state.featureGroup);
                        state.map.fitBounds(state.polygon.getBounds(), { padding: [30, 30] });
                    }

                    sync();
                    updateToolbar();
                    hint(lls.length >= 3 ? 'Polygon tersimpan ('.concat(lls.length, ' titik).') : 'Belum ada polygon.');
                }

                // ---- Bootstrap peta ----
                state.map = L.map(mapEl, {
                    zoomControl: true,
                }).setView([center.lat, center.lng], 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                }).addTo(state.map);

                state.featureGroup = new L.FeatureGroup().addTo(state.map);
                state.markerLayer = new L.LayerGroup().addTo(state.map);

                if (initPoints.length >= 3) {
                    const lls = initPoints.map((p) => [p.lat, p.lng]);
                    state.polygon = L.polygon(lls, polygonStyle(state.color)).addTo(state.featureGroup);
                    state.map.fitBounds(state.polygon.getBounds(), { padding: [30, 30] });
                } else {
                    state.map.setView([center.lat, center.lng], 15);
                }

                state.map.on('draw:created', (e) => {
                    cancelDrawing();
                    if (state.polygon) {
                        state.featureGroup.removeLayer(state.polygon);
                    }
                    state.polygon = e.layer;
                    state.polygon.setStyle(polygonStyle(state.color));
                    state.featureGroup.addLayer(state.polygon);
                    sync();
                    updateToolbar();
                    hint('Polygon selesai ('.concat(ringLatLngs().length, ' titik). Area dihitung otomatis.'));
                });

                document.getElementById('tb-draw').addEventListener('click', () => {
                    if (state.drawing) {
                        cancelDrawing();
                        hint('Menggambar dibatalkan.');
                    } else {
                        startDrawing();
                    }
                });

                document.getElementById('tb-finish').addEventListener('click', finishDrawing);
                document.getElementById('tb-edit').addEventListener('click', toggleEdit);
                document.getElementById('tb-clear').addEventListener('click', clearAll);

                window.__ttFenceInited = true;
                window.ttFenceFormMap = {
                    get map() { return state.map; },
                    get points() { return ringLatLngs().map((ll) => ({ lat: ll.lat, lng: ll.lng })); },
                    setPolygon,
                    clear: clearAll,
                    setColor,
                    startDrawing,
                    finishDrawing,
                    toggleEdit,
                };

                updateToolbar();
                })();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', __ttInitFenceForm);
            } else {
                __ttInitFenceForm();
            }
        </script>
    @endassets
</div>