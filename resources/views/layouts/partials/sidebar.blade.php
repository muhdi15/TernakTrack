@props(['inDrawer' => false])

<div class="flex h-full flex-col">
    {{-- Logo --}}
    <div class="flex h-16 shrink-0 items-center gap-2.5 border-b border-gray-200 px-5 dark:border-gray-800">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-white shadow-sm">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19.5c0 0-5.25-3.75-5.25-10.125A8.625 8.625 0 0112 5.25c0 0 5.25 3.75 5.25 10.125A8.625 8.625 0 0112 19.5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 8.25L12 12l-1.5 2.25M12 12l2.25 1.5"/></svg>
        </span>
        <div class="leading-tight">
            <p class="text-sm font-extrabold tracking-tight text-gray-900 dark:text-white">Ternak<span class="text-brand-600 dark:text-brand-400">Track</span></p>
            <p class="text-[10px] font-medium text-gray-400 dark:text-gray-500">GPS + Virtual Fence</p>
        </div>
        @if ($inDrawer)
            <button @click="drawerOpen = false" class="ms-auto -me-1 rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800">
                <x-icon name="x" class="h-5 w-5" />
            </button>
        @endif
    </div>

    {{-- Menu --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4">
        <p class="mb-2 px-2 text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Menu Utama</p>
        <ul class="space-y-1">
            @foreach ($layoutMenu as $item)
                @php
                    $active = $currentRouteName === $item['route']
                        || (in_array($item['route'], ['animals.index', 'devices.index', 'fences.index', 'calibration.index', 'logs.index', 'alerts.index', 'reports.index', 'settings.index']) && request()->is(explode('.', $item['route'])[0]));
                @endphp
                <li>
                    <a
                        href="{{ route($item['route']) }}"
                        @class([
                            'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                            'bg-brand-50 text-brand-700 dark:bg-brand-950/60 dark:text-brand-300' => $active,
                            'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white' => ! $active,
                        ])
                    >
                        <x-icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                        <span class="flex-1 truncate">{{ $item['label'] }}</span>
                        @if ($item['route'] === 'alerts.index')
                            <livewire:notification-badge-count />
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    {{-- Footer: profil + logout --}}
    <div class="shrink-0 border-t border-gray-200 p-3 dark:border-gray-800">
        <div class="flex items-center gap-3 rounded-xl p-2 transition hover:bg-gray-100 dark:hover:bg-gray-800">
            <a href="{{ route('profile.edit') }}" class="flex min-w-0 flex-1 items-center gap-3">
                <span class="relative flex h-9 w-9 shrink-0 overflow-hidden rounded-full bg-brand-100 ring-1 ring-gray-200 dark:bg-brand-950 dark:ring-gray-700">
                    @if (auth()->user()->avatar_path)
                        <img src="{{ asset('storage/'.auth()->user()->avatar_path) }}" alt="" class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full w-full items-center justify-center text-sm font-bold text-brand-700 dark:text-brand-300">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>
                    @endif
                </span>
                <span class="min-w-0 leading-tight">
                    <span class="block truncate text-sm font-semibold text-gray-900 dark:text-white">{{ auth()->user()->name }}</span>
                    <span class="block truncate text-xs capitalize text-gray-400 dark:text-gray-500">{{ auth()->user()->role }}</span>
                </span>
            </a>
            <form method="POST" action="{{ route('logout') }}" x-data @submit.prevent="window.Alpine.confirmModal('Keluar dari akun?', 'Anda akan diarahkan ke halaman login.', () => $el.submit())">
                @csrf
                <button type="submit" class="rounded-lg p-2 text-gray-400 transition hover:bg-red-50 hover:text-danger dark:hover:bg-red-950/40" title="Keluar">
                    <x-icon name="logout" class="h-5 w-5" />
                </button>
            </form>
        </div>
    </div>
</div>