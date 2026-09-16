<div
    x-data="{ open: false }"
    @click.outside="open = false"
    class="relative"
    wire:poll.15s
    @alert-toast.window="Alpine.store('toast').success($event.detail.title, $event.detail.message)"
>
    <button
        @click="open = ! open"
        class="relative flex h-10 w-10 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
        aria-label="Notifikasi"
    >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" x-cloak>
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
        </svg>

        <template x-if="{{ $unreadCount }} > 0">
            <span class="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-danger px-1 text-[10px] font-bold text-white">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        </template>
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute end-0 z-50 mt-2 w-80 origin-top-right rounded-xl bg-white dark:bg-gray-800 shadow-lg ring-1 ring-gray-200 dark:ring-gray-700"
        style="display: none"
    >
        <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 px-4 py-3">
            <p class="text-sm font-semibold text-gray-800 dark:text-white">Notifikasi</p>
            @if ($unreadCount > 0)
                <button
                    wire:click="markAllAsRead"
                    class="text-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400"
                >Tandai semua dibaca</button>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse ($recentAlerts as $alert)
                <a
                    href="{{ route('alerts.index') }}"
                    wire:click="markAsRead({{ $alert->id }})"
                    class="block border-b border-gray-50 dark:border-gray-700/60 px-4 py-3 transition hover:bg-gray-50 dark:hover:bg-gray-700/50"
                >
                    <div class="flex items-start gap-3">
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
                                'font-normal text-gray-600 dark:text-gray-300' => $alert->is_read,
                            ])>{{ $alert->title }}</p>
                            <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $alert->message }}</p>
                            <p class="mt-0.5 text-[11px] text-gray-400 dark:text-gray-500">{{ $alert->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </a>
            @empty
                <p class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada notifikasi</p>
            @endforelse
        </div>

        <a href="{{ route('alerts.index') }}" class="block border-t border-gray-100 dark:border-gray-700 px-4 py-2.5 text-center text-xs font-semibold text-brand-600 hover:bg-gray-50 dark:text-brand-400 dark:hover:bg-gray-700/50">
            Lihat semua alert →
        </a>
    </div>
</div>