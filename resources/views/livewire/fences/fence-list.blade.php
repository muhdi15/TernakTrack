<div wire:poll.30s>
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Kelola zona virtual: gambar polygon pada peta, tetapkan hewan yang dilindungi, dan pantau status keluar/masuk.
            </p>
        </div>
        <a href="{{ route('fences.create') }}" class="tt-btn-primary">
            + Buat Fence
        </a>
    </div>

    @if (session('status'))
        <div class="mt-4 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-700 dark:bg-green-900/40 dark:text-green-200">
            {{ session('status') }}
        </div>
    @endif

    <div class="tt-card mt-5 overflow-hidden p-0">
        <div class="flex flex-col gap-3 border-b border-slate-200 p-4 dark:border-slate-700 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap gap-2 text-sm">
                <button
                    type="button"
                    wire:click="$set('fenceType', '')"
                    class="tt-badge cursor-pointer hover:opacity-80 @if($fenceType === '') bg-slate-600 text-white @endif"
                >Semua</button>
                <button
                    type="button"
                    wire:click="$set('fenceType', 'inclusion')"
                    class="tt-badge cursor-pointer hover:opacity-80 @if($fenceType === 'inclusion') bg-slate-600 text-white @endif"
                >Inclusion</button>
                <button
                    type="button"
                    wire:click="$set('fenceType', 'exclusion')"
                    class="tt-badge cursor-pointer hover:opacity-80 @if($fenceType === 'exclusion') bg-slate-600 text-white @endif"
                >Exclusion</button>
            </div>

            <div class="flex flex-wrap gap-2">
                <select wire:model.live="status" class="tt-input !w-auto">
                    <option value="">Status: Semua</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Nonaktif</option>
                </select>
                <select wire:model.live="farmId" class="tt-input !w-auto">
                    <option value="">Semua Lokasi</option>
                    @foreach ($farms as $farm)
                        <option value="{{ $farm->id }}">{{ $farm->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($fences as $fence)
            <article wire:key="fence-{{ $fence->id }}" class="tt-card overflow-hidden p-0">
                <a href="{{ route('fences.show', $fence) }}" class="block">
                    <div
                        class="fence-mini-map"
                        data-coords='@json($fence->polygon_coordinates ?: [], JSON_HEX_APOS)'
                        data-color="{{ $fence->color }}"
                    ></div>
                </a>

                <div class="p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <a href="{{ route('fences.show', $fence) }}" class="truncate font-semibold text-slate-800 hover:text-brand-600 dark:text-slate-100 dark:hover:text-brand-400">
                                {{ $fence->name }}
                            </a>
                            <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                <span class="tt-badge {{ $fence->fence_type === 'inclusion' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' }}">
                                    {{ $fence->fence_type === 'inclusion' ? 'Inclusion' : 'Exclusion' }}
                                </span>
                                <span class="tt-badge {{ $fence->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300' }}">
                                    {{ $fence->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                        </div>

                        <button
                            type="button"
                            wire:click="toggleActive({{ $fence->id }})"
                            title="{{ $fence->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition {{ $fence->is_active ? 'bg-green-500' : 'bg-slate-300 dark:bg-slate-600' }}"
                        >
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition {{ $fence->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <div class="rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-800/60">
                            <div class="text-xs text-slate-400">Luas</div>
                            <div class="font-semibold tabular-nums text-slate-700 dark:text-slate-200">
                                {{ number_format((float) $fence->area_hectares, 4, ',', '.') }} ha
                            </div>
                        </div>
                        <div class="rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-800/60">
                            <div class="text-xs text-slate-400">Hewan</div>
                            <div class="font-semibold tabular-nums text-slate-700 dark:text-slate-200">
                                {{ $fence->animals_count }} ekor
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-1.5">
                        <a href="{{ route('fences.show', $fence) }}" class="tt-btn-secondary px-2.5 py-1.5 text-xs">Detail</a>
                        <a href="{{ route('fences.edit', $fence) }}" class="tt-btn-secondary px-2.5 py-1.5 text-xs">Edit</a>
                        <a
                            href="{{ route('calibration.index') }}"
                            title="Fitur kalibrasi GPS tersedia pada sesi khusus kalibrasi."
                            class="tt-btn-secondary px-2.5 py-1.5 text-xs"
                        >
                            Kalibrasi via HP 📱
                        </a>
                        <button
                            x-data
                            x-on:click.prevent='window.Alpine.confirmModal(
                                "Hapus Fence?",
                                "Fence {{ $fence->name }} beserta riwayat event akan dihapus permanen.",
                                () => $wire.delete("{{ $fence->id }}")
                            )'
                            class="tt-btn-secondary px-2.5 py-1.5 text-xs text-red-600 hover:border-red-300 dark:text-red-400"
                        >Hapus</button>
                    </div>
                </div>
            </article>
        @empty
            <div class="tt-card p-8 text-center sm:col-span-2 xl:col-span-3">
                <p class="text-sm text-slate-400">Belum ada fence. Buat zona virtual pertama Anda.</p>
                <a href="{{ route('fences.create') }}" class="tt-btn-primary mt-4">+ Buat Fence</a>
            </div>
        @endforelse
    </div>

    @assets
        <script>
            function __ttInitFenceListMaps() {
                window.TT.initMiniFenceMaps();

                document.addEventListener('livewire:init', () => {
                    Livewire.hook('commit', ({ succeed }) => {
                        succeed(() => window.TT.initMiniFenceMaps());
                    });
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', __ttInitFenceListMaps);
            } else {
                __ttInitFenceListMaps();
            }
        </script>
    @endassets
</div>