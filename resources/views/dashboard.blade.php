<x-app-layout>
    <x-slot name="subtitle">
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pantau kondisi ternak Anda secara real-time.</p>
    </x-slot>

    <div class="space-y-5">
        {{-- Kartu statistik (Livewire) --}}
        <livewire:dashboard.dashboard-stats />

        <div class="grid gap-5 lg:grid-cols-3">
            {{-- Peta (placeholder Sesi 5) --}}
            <div class="tt-card overflow-hidden lg:col-span-2">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Peta Real-time</h2>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Marker hewan & polygon fence</p>
                    </div>
                    <a href="{{ route('map') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400">Buka →
                    </a>
                </div>
                <div class="relative flex h-72 items-center justify-center bg-slate-100 dark:bg-gray-900">
                    <div class="absolute inset-0 opacity-[0.35] dark:opacity-20" style="background-image: linear-gradient(#cbd5e1 1px, transparent 1px), linear-gradient(90deg, #cbd5e1 1px, transparent 1px); background-size: 32px 32px;"></div>
                    <div class="relative z-10 flex flex-col items-center gap-2 text-center">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-brand-600 shadow-lg dark:bg-gray-800 dark:text-brand-400">
                            <x-icon name="map" class="h-6 w-6" />
                        </span>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Peta akan tampil di sini</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Implementasi di Sesi 5</p>
                    </div>
                </div>
            </div>

            {{-- Alert terbaru --}}
            <div class="tt-card flex flex-col overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Alert Terbaru</h2>
                    <a href="{{ route('alerts.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400">Semua
                        →
                    </a>
                </div>
                <div class="flex-1 divide-y divide-gray-100 dark:divide-gray-700/60">
                    @forelse (\App\Models\Alert::where('user_id', auth()->id())->latest()->limit(5)->get() as $alert)
                        <a href="{{ route('alerts.index') }}" class="flex items-start gap-3 px-5 py-3.5 transition hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <span @class([
                                'mt-1 h-2 w-2 shrink-0 rounded-full',
                                'bg-brand-500' => $alert->severity === 'info',
                                'bg-warn-500' => $alert->severity === 'warning',
                                'bg-danger' => $alert->severity === 'critical',
                            ])></span>
                            <div class="min-w-0 flex-1">
                                <p @class([
                                    'truncate text-sm',
                                    'font-semibold text-gray-900 dark:text-white' => ! $alert->is_read,
                                    'text-gray-600 dark:text-gray-300' => $alert->is_read,
                                ])>{{ $alert->title }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ $alert->created_at->diffForHumans() }}</p>
                            </div>
                            <span @class([
                                'tt-badge',
                                'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' => $alert->severity === 'info',
                                'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300' => $alert->severity === 'warning',
                                'bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-300' => $alert->severity === 'critical',
                            ])>{{ $alert->severity }}</span>
                        </a>
                    @empty
                        <p class="px-5 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Tidak ada alert</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            {{-- Perangkat perlu perhatian --}}
            <div class="tt-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Perangkat Perlu Perhatian</h2>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Offline, baterai rendah, atau status khusus</p>
                    </div>
                    <a href="{{ route('devices.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400">Kelola
                        →
                    </a>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700/60">
                    @php
                        $attentionDevices = \App\Models\Device::where('user_id', auth()->id())
                            ->where(fn ($q) => $q->whereNull('last_seen_at')->orWhere('last_seen_at', '<', now()->subMinutes(5))->orWhere('battery_level', '<=', 20)->orWhere('status', '!=', 'active'))
                            ->latest('last_seen_at')
                            ->limit(6)
                            ->get();
                    @endphp
                    @forelse ($attentionDevices as $device)
                        <div class="flex items-center gap-3 px-5 py-3.5">
                            <span @class([
                                'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg',
                                'bg-green-100 text-brand-600 dark:bg-brand-950/60 dark:text-brand-400' => $device->status === 'active',
                                'bg-amber-100 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400' => $device->status === 'maintenance',
                                'bg-red-100 text-red-600 dark:bg-red-950/50 dark:text-red-400' => $device->status === 'lost' || $device->status === 'inactive',
                            ])>
                                <x-icon name="chip" class="h-4 w-4" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $device->name }}</p>
                                <p class="truncate text-xs text-gray-400 dark:text-gray-500">{{ $device->device_code }}</p>
                            </div>
                            <div class="text-end">
                                <span @class([
                                    'tt-badge',
                                    'bg-green-100 text-green-700 dark:bg-brand-950/60 dark:text-brand-300' => $device->status === 'active',
                                    'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300' => $device->status === 'maintenance',
                                    'bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-300' => in_array($device->status, ['lost', 'inactive']),
                                ])>{{ $device->status }}</span>
                                @if ($device->battery_level !== null && $device->battery_level <= 20)
                                    <p class="mt-1 text-[11px] font-semibold text-danger">🔋 {{ $device->battery_level }}%</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="px-5 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Semua perangkat dalam kondisi baik</p>
                    @endforelse
                </div>
            </div>

            {{-- Grafik placeholder (Sesi 7) --}}
            <div class="tt-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Aktivitas 7 Hari</h2>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Grafik akan tampil di sini</p>
                    </div>
                    <a href="{{ route('reports.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400">Laporan
                        →
                    </a>
                </div>
                <div class="flex h-56 items-center justify-center">
                    <div class="flex h-full w-full items-end justify-between gap-2 px-6 pb-6 pt-4">
                        @foreach (range(1, 12) as $bar)
                            <div class="flex h-full w-full flex-col items-center justify-end gap-1.5">
                                <div class="w-full rounded-t-md bg-brand-200 dark:bg-brand-900" style="height: {{ rand(20, 100) }}%"></div>
                                <span class="text-[10px] text-gray-400 dark:text-gray-500">#{{ $bar }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>