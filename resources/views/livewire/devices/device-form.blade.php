<div>
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">
                {{ $device ? 'Edit Perangkat' : 'Tambah Perangkat' }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ $device ? 'Perbarui informasi perangkat '.$device->device_code : 'Daftarkan tracker GPS baru di peternakan.' }}
            </p>
        </div>
        <a href="{{ route('devices.index') }}" class="tt-btn-secondary">← Kembali</a>
    </div>

    <form wire:submit="save" class="tt-card mt-5 max-w-2xl space-y-5">
        <div>
            <label class="tt-label" for="device-name">Nama Perangkat</label>
            <input id="device-name" type="text" wire:model.live="name" class="tt-input" placeholder="cth. GPS-01 Sapi Titan">
            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="tt-label" for="device-code">Kode Perangkat</label>
            <input id="device-code" type="text" wire:model.live="device_code" class="tt-input" placeholder="cth. TT-GPS-0001">
            <p class="mt-1 text-xs text-slate-400">Kode unik yang dicetak pada perangkat.</p>
            @error('device_code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="tt-label" for="device-status">Status</label>
                <select id="device-status" wire:model.live="status" class="tt-input">
                    <option value="active">Aktif</option>
                    <option value="inactive">Nonaktif</option>
                    <option value="maintenance">Perbaikan</option>
                    <option value="lost">Hilang</option>
                </select>
                @error('status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="tt-label" for="device-farm">Farm / Kandang Lokasi</label>
                <select id="device-farm" wire:model.live="farm_id" class="tt-input">
                    <option value="">— Pilih (opsional) —</option>
                    @foreach ($farms as $farm)
                        <option value="{{ $farm->id }}">{{ $farm->name }}</option>
                    @endforeach
                </select>
                @error('farm_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="tt-label" for="device-notes">Catatan</label>
            <textarea id="device-notes" rows="3" wire:model.live="notes" class="tt-input" placeholder="Opsional: lokasi pemasangan, riwayat servis, dll."></textarea>
            @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-3 border-t border-slate-200 pt-5 dark:border-slate-700">
            <button type="submit" class="tt-btn-primary">
                {{ $device ? 'Simpan Perubahan' : 'Simpan & Dapatkan Token' }}
            </button>
            <a href="{{ route('devices.index') }}" class="tt-btn-secondary">Batal</a>
        </div>
    </form>

    {{-- Modal Token API (hanya muncul sekali setelah perangkat baru dibuat) --}}
    @if ($showTokenModal && $createdToken)
        <div
            x-data="{
                copied: false,
                token: @js($createdToken),
                async copyToken() {
                    try {
                        await navigator.clipboard.writeText(this.token);
                        this.copied = true;
                    } catch (e) { /* clipboard tidak tersedia */ }
                }
            }"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
        >
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-slate-800">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100">Perangkat Berhasil Dibuat</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Salin token API di bawah ini.</p>
                    </div>
                </div>

                <div x-cloak class="mt-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-900/40 dark:text-amber-200">
                    Token hanya ditampilkan <strong>satu kali</strong>. Jika hilang, Anda harus melakukan
                    <em>regenerasi token</em> dari halaman detail perangkat.
                </div>

                <div class="mt-4 flex items-center gap-2">
                    <input
                        x-bind:value="token"
                        readonly
                        class="tt-input flex-1 font-mono text-xs"
                        x-on:focus="$el.select()"
                    >
                    <button
                        type="button"
                        x-on:click="copyToken()"
                        class="tt-btn-primary px-3 py-2 text-xs"
                    >
                        <span x-show="!copied">Salin</span>
                        <span x-show="copied" x-cloak>✓ Tersalin</span>
                    </button>
                </div>

                <div class="mt-5 flex justify-end">
                    <button type="button" x-on:click="$wire.closeTokenModal()" class="tt-btn-secondary">Selesai</button>
                </div>
            </div>
        </div>
    @endif
</div>