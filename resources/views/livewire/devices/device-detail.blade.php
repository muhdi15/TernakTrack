<div>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('devices.index') }}" class="tt-btn-secondary">← Kembali</a>
            <div>
                <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ $device->name }}</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $device->device_code }}</p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('devices.edit', $device) }}" class="tt-btn-secondary">Edit</a>
            <button
                x-data
                x-on:click="$wire.regenerateToken()"
                class="tt-btn-secondary"
            >Regenerasi Token</button>
        </div>
    </div>

    @if (session('status'))
        <div class="mt-4 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-700 dark:bg-green-900/40 dark:text-green-200">
            {{ session('status') }}
        </div>
    @endif

    <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="tt-card">
            <p class="text-xs uppercase tracking-wide text-slate-400">Status</p>
            <p class="mt-1 text-lg font-semibold">
                @if ($device->isOnline())
                    <span class="text-emerald-600 dark:text-emerald-400">● Online</span>
                @else
                    <span class="text-slate-400">○ Offline</span>
                @endif
            </p>
        </div>
        <div class="tt-card">
            <p class="text-xs uppercase tracking-wide text-slate-400">Baterai</p>
            <p class="mt-1 text-lg font-semibold tabular-nums">
                {{ $device->battery_level ?? '—' }}%
                <span class="text-sm font-normal text-slate-400">· {{ $device->battery_voltage ? $device->battery_voltage.'V' : '—' }}</span>
            </p>
        </div>
        <div class="tt-card">
            <p class="text-xs uppercase tracking-wide text-slate-400">Laporan Terakhir</p>
            <p class="mt-1 text-lg font-semibold">{{ $device->last_seen_at ? $device->last_seen_at->diffForHumans() : 'Belum pernah' }}</p>
        </div>
        <div class="tt-card">
            <p class="text-xs uppercase tracking-wide text-slate-400">Firmware</p>
            <p class="mt-1 text-lg font-semibold">{{ $device->firmware_version ?? '—' }}</p>
        </div>
    </div>

    {{-- Baterai 7 hari --}}
    <div class="tt-card mt-5">
        <h2 class="mb-3 text-base font-semibold text-slate-800 dark:text-slate-100">Level Baterai 7 Hari Terakhir</h2>
        <div wire:ignore class="h-64">
            <canvas id="battery-chart" wire:ignore></canvas>
        </div>
    </div>

    {{-- Rute 24 jam --}}
    <div class="tt-card mt-5">
        <h2 class="mb-1 text-base font-semibold text-slate-800 dark:text-slate-100">Riwayat Lokasi 24 Jam Terakhir</h2>
        <p class="mb-3 text-sm text-slate-500 dark:text-slate-400">Polyline pergerakan perangkat, marker hijau = titik awal.</p>
        <div wire:ignore id="route-map" class="h-80 rounded-lg"></div>
    </div>

    <div class="mt-5 grid gap-4 lg:grid-cols-2">
        {{-- Kirim perintah --}}
        <div class="tt-card">
            <h2 class="mb-3 text-base font-semibold text-slate-800 dark:text-slate-100">Kirim Perintah</h2>
            <div class="space-y-4">
                <div>
                    <label class="tt-label" for="command-type">Jenis Perintah</label>
                    <select id="command-type" wire:model.live="selectedCommand" class="tt-input">
                        <option value="reboot">🔁 Restart Perangkat (reboot)</option>
                        <option value="set_interval">⏱ Ubah Interval Laporan (set_interval)</option>
                        <option value="locate_now">📍 Lacak Sekarang (locate_now)</option>
                    </select>
                </div>

                @if ($selectedCommand === 'set_interval')
                    <div>
                        <label class="tt-label" for="interval-seconds">Interval Laporan (detik)</label>
                        <input id="interval-seconds" type="number" min="15" max="3600" wire:model.live="intervalSeconds" class="tt-input">
                        @error('intervalSeconds') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                <button type="button" wire:click="sendCommand" class="tt-btn-primary">Kirim Perintah</button>
                <p class="text-xs text-slate-400">Perintah disimpan di antrean; perangkat mengambilnya pada laporan/heartbeat berikutnya.</p>
            </div>
        </div>

        {{-- Riwayat perintah --}}
        <div class="tt-card overflow-hidden p-0">
            <h2 class="border-b border-slate-200 px-5 py-4 text-base font-semibold text-slate-800 dark:border-slate-700 dark:text-slate-100">
                Riwayat Perintah ({{ $commands->count() }})
            </h2>
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                @forelse ($commands as $command)
                    <div class="flex items-center justify-between px-5 py-3 text-sm">
                        <div>
                            <div class="font-medium text-slate-700 dark:text-slate-200">
                                {{ match ($command->command) {
                                    'reboot' => 'Restart',
                                    'set_interval' => 'Ubah Interval · '.($command->payload['interval_seconds'] ?? '?').'s',
                                    'locate_now' => 'Lacak Sekarang',
                                    default => $command->command,
                                } }}
                            </div>
                            <div class="text-xs text-slate-400">{{ $command->created_at->diffForHumans() }}</div>
                        </div>
                        @php
                            $cmdStatus = match ($command->status) {
                                'pending' => ['bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300', 'Menunggu'],
                                'sent' => ['bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300', 'Terkirim'],
                                'executed' => ['bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300', 'Selesai'],
                                'failed' => ['bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300', 'Gagal'],
                                default => ['bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300', $command->status],
                            };
                        @endphp
                        <span class="{{ $cmdStatus[0] }} tt-badge">{{ $cmdStatus[1] }}</span>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-slate-400">Belum ada perintah.</p>
                @endforelse
            </div>
        </div>
    </div>

    @assets
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.TT.batteryChart(
                document.getElementById('battery-chart'),
                {!! json_encode($batteryLabels) !!},
                {!! json_encode($batteryValues) !!},
            );

            window.TT.routeMap('route-map', {!! json_encode($routePoints) !!});
        });
    </script>
    @endassets

    {{-- Modal konfirmasi regenerasi token --}}
    @if ($showRegenerateModal && $regeneratedToken)
        <div
            x-data="{
                typed: '',
                copied: false,
                token: @js($regeneratedToken),
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
                <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100">Regenerasi Token API</h2>

                <div class="mt-3 flex items-start gap-2 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-900/40 dark:text-amber-200">
                    <span>Token lama <strong>langsung tidak berlaku</strong> dan hanya dapat dilihat satu kali. Perangkat harus dikonfigurasi ulang dengan token baru.<br>Ketik <strong>REGENERATE</strong> untuk melanjutkan.</span>
                </div>

                <input
                    x-model="typed"
                    placeholder="Ketik REGENERATE"
                    class="tt-input mt-4"
                >

                <div x-cloak x-show="typed === 'REGENERATE'" class="mt-4 flex items-center gap-2">
                    <input
                        x-bind:value="token"
                        readonly
                        class="tt-input flex-1 font-mono text-xs"
                        x-on:focus="$el.select()"
                    >
                    <button type="button" x-on:click="copyToken()" class="tt-btn-primary px-3 py-2 text-xs">
                        <span x-show="!copied">Salin</span>
                        <span x-show="copied" x-cloak>✓ Tersalin</span>
                    </button>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" x-on:click="$wire.closeRegenerateModal()" class="tt-btn-secondary">Tutup</button>
                </div>
            </div>
        </div>
    @endif
</div>