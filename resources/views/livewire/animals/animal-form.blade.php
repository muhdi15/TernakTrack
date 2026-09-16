<div>
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">
                {{ $animal ? 'Edit Hewan' : 'Tambah Hewan' }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ $animal ? 'Perbarui data hewan '.$animal->tag_number : 'Daftarkan hewan ternak baru.' }}
            </p>
        </div>
        <a href="{{ route('animals.index') }}" class="tt-btn-secondary">← Kembali</a>
    </div>

    <form wire:submit="save" class="tt-card mt-5 max-w-3xl space-y-5">
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="tt-label" for="animal-name">Nama Hewan</label>
                <input id="animal-name" type="text" wire:model.live="name" class="tt-input" placeholder="cth. Titan">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="tt-label" for="animal-tag">Nomor Tag (Ear Tag)</label>
                <input id="animal-tag" type="text" wire:model.live="tag_number" class="tt-input" placeholder="cth. TT-SPI-0001">
                @error('tag_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-3">
            <div>
                <label class="tt-label" for="animal-species">Spesies</label>
                <select id="animal-species" wire:model.live="species" class="tt-input">
                    @foreach (\App\Livewire\Animals\AnimalForm::SPECIES as $item)
                        <option value="{{ $item }}">{{ ucfirst($item) }}</option>
                    @endforeach
                </select>
                @error('species') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="tt-label">Jenis Kelamin</label>
                <div class="mt-2 flex gap-4">
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="radio" wire:model.live="gender" value="jantan" class="accent-indigo-600"> ♂ Jantan
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="radio" wire:model.live="gender" value="betina" class="accent-indigo-600"> ♀ Betina
                    </label>
                </div>
                @error('gender') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="tt-label" for="animal-health">Status Kesehatan</label>
                <select id="animal-health" wire:model.live="health_status" class="tt-input">
                    @foreach (\App\Livewire\Animals\AnimalForm::HEALTH_STATUS as $item)
                        <option value="{{ $item }}">{{ ucfirst($item) }}</option>
                    @endforeach
                </select>
                @error('health_status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-3">
            <div>
                <label class="tt-label" for="animal-birth">Tanggal Lahir</label>
                <input id="animal-birth" type="date" wire:model.live="birth_date" class="tt-input">
                @error('birth_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="tt-label" for="animal-weight">Berat (kg)</label>
                <input id="animal-weight" type="number" step="0.1" min="0" wire:model.live="weight_kg" class="tt-input" placeholder="cth. 650.5">
                @error('weight_kg') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="tt-label" for="animal-farm">Farm / Kandang</label>
                <select id="animal-farm" wire:model.live="farm_id" class="tt-input">
                    <option value="">— Pilih (opsional) —</option>
                    @foreach ($farms as $farm)
                        <option value="{{ $farm->id }}">{{ $farm->name }}</option>
                    @endforeach
                </select>
                @error('farm_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="tt-label" for="animal-device">Pasang Tracker GPS</label>
            <select id="animal-device" wire:model.live="device_id" class="tt-input">
                <option value="">— Belum ada tracker —</option>
                @foreach ($devices as $device)
                    <option value="{{ $device->id }}">{{ $device->name }} ({{ $device->device_code }})</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-slate-400">Hanya menampilkan perangkat yang belum terpasang pada hewan lain.</p>
            @error('device_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="tt-label">Foto Hewan (max 2 MB, JPG/PNG, otomatis diperkecil 800px)</label>
            <div class="flex items-center gap-4">
                <div class="h-24 w-24 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-100 dark:border-slate-700 dark:bg-slate-800">
                    @if ($photo)
                        <img src="{{ $photo->temporaryUrl() }}" class="h-full w-full object-cover" alt="Pratinjau">
                    @elseif ($animal && $animal->photo_path)
                        <img src="{{ asset('storage/'.$animal->photo_path) }}" class="h-full w-full object-cover" alt="{{ $animal->name }}">
                    @else
                        <span class="flex h-full items-center justify-center text-xs text-slate-400">Belum ada</span>
                    @endif
                </div>
                <input type="file" wire:model.live="photo" accept="image/jpeg,image/png" class="block text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-600 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-indigo-700 dark:text-slate-300">
            </div>
            @error('photo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="tt-label">Zona Virtual Fences</label>
            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                @forelse ($fences as $fence)
                    <label class="flex items-start gap-2 rounded-lg border border-slate-200 p-3 text-sm hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800/50">
                        <input
                            type="checkbox"
                            wire:model.live="selectedFences"
                            value="{{ $fence->id }}"
                            class="mt-0.5 accent-indigo-600"
                        >
                        <span>
                            <span class="font-medium text-slate-700 dark:text-slate-200">{{ $fence->name }}</span>
                            <span class="block text-xs text-slate-400">
                                {{ $fence->fence_type === 'inclusion' ? 'Zona Inklusi (di dalam = aman)' : 'Zona Eksklusi (di dalam = terlarang)' }}
                            </span>
                        </span>
                    </label>
                @empty
                    <p class="text-sm text-slate-400">Belum ada pagar virtual. Buat di modul <a href="#" class="text-indigo-600 underline">Fences</a>.</p>
                @endforelse
            </div>
            @error('selectedFences') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="tt-label" for="animal-notes">Catatan</label>
            <textarea id="animal-notes" rows="3" wire:model.live="notes" class="tt-input" placeholder="Opsional: riwayat ternak, kondisi khusus, dll."></textarea>
            @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-3 border-t border-slate-200 pt-5 dark:border-slate-700">
            <button type="submit" class="tt-btn-primary">
                {{ $animal ? 'Simpan Perubahan' : 'Simpan Hewan' }}
            </button>
            <a href="{{ route('animals.index') }}" class="tt-btn-secondary">Batal</a>
        </div>
    </form>
</div>