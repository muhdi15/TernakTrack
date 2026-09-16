<div>
    @if ($open)
        <div
            x-data
            x-init="$el.classList.add('opacity-100')"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 opacity-0 transition-opacity"
        >
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-slate-800">
                <div class="flex items-start justify-between">
                    <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100">
                        {{ $recordId ? 'Edit Rekam Medis' : 'Tambah Rekam Medis' }}
                    </h2>
                    <button type="button" wire:click="cancel" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">✕</button>
                </div>

                <form wire:submit="save" class="mt-4 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="tt-label" for="hr-date">Tanggal</label>
                            <input id="hr-date" type="date" wire:model.live="record_date" class="tt-input">
                            @error('record_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="tt-label" for="hr-type">Jenis</label>
                            <select id="hr-type" wire:model.live="type" class="tt-input">
                                @foreach (\App\Livewire\Animals\HealthRecordForm::TYPES as $item)
                                    <option value="{{ $item }}">{{ ucfirst($item) }}</option>
                                @endforeach
                            </select>
                            @error('type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="tt-label" for="hr-desc">Deskripsi</label>
                        <textarea id="hr-desc" rows="3" wire:model.live="description" class="tt-input" placeholder="Keterangan pemeriksaan / tindakan..."></textarea>
                        @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="tt-label" for="hr-vet">Nama Dokter (opsional)</label>
                            <input id="hr-vet" type="text" wire:model.live="vet_name" class="tt-input" placeholder="cth. drh. Budi">
                            @error('vet_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="tt-label" for="hr-due">Tenggat Tindakan Berikutnya</label>
                            <input id="hr-due" type="date" wire:model.live="next_due_date" class="tt-input">
                            @error('next_due_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-700">
                        <button type="button" wire:click="cancel" class="tt-btn-secondary">Batal</button>
                        <button type="submit" class="tt-btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>