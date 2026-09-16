<header class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-3 border-b border-gray-200 bg-white/90 px-4 backdrop-blur dark:border-gray-800 dark:bg-gray-900/90 sm:px-6">
    {{-- Hamburger (mobile) --}}
    <button @click="drawerOpen = true" class="-ms-1 rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 lg:hidden" aria-label="Buka menu">
        <x-icon name="menu" class="h-5 w-5" />
    </button>

    {{-- Logo (mobile) --}}
    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 lg:hidden">
        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 text-white">
            <x-icon name="leaf" class="h-4 w-4" />
        </span>
        <span class="text-sm font-extrabold tracking-tight text-gray-900 dark:text-white">Ternak<span class="text-brand-600 dark:text-brand-400">Track</span></span>
    </a>

    {{-- Search global --}}
    <div class="relative hidden min-w-0 flex-1 max-w-md md:block">
        <x-icon name="search" class="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
        <input
            type="search"
            placeholder="Cari hewan, perangkat, fence..."
            class="tt-input ps-9"
            x-data
            @keydown.enter.prevent="$store.toast.info('Pencarian', 'Fitur pencarian global akan tersedia di sesi berikutnya.')"
        >
    </div>

    <div class="ms-auto flex items-center gap-1.5">
        {{-- Farm selector --}}
        <div class="relative" x-data="{ farmOpen: false }" @click.outside="farmOpen = false">
            <button @click="farmOpen = !farmOpen" class="flex items-center gap-2 rounded-lg px-2.5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800" aria-label="Pilih farm">
                <x-icon name="building" class="h-4 w-4 text-gray-400" />
                <span class="hidden max-w-40 truncate sm:inline">{{ $layoutActiveFarm?->name ?? 'Pilih Farm' }}</span>
                <x-icon name="chevron-down" class="hidden h-3.5 w-3.5 text-gray-400 sm:block" />
            </button>
            <div
                x-show="farmOpen"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute end-0 z-50 mt-1 w-64 origin-top-right rounded-xl bg-white py-1.5 shadow-lg ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700"
                style="display: none"
            >
                <p class="px-3 py-1.5 text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Pilih Farm</p>
                @forelse ($layoutFarms as $farm)
                    <button
                        @click="fetch('{{ route('farm.select') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify({ farm_id: {{ $farm->id }} }) }).then(() => location.reload())"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm transition hover:bg-gray-50 dark:hover:bg-gray-700/60"
                    >
                        <span @class([
                            'h-1.5 w-1.5 shrink-0 rounded-full',
                            'bg-brand-500' => $layoutActiveFarm?->id === $farm->id,
                            'bg-gray-300 dark:bg-gray-600' => $layoutActiveFarm?->id !== $farm->id,
                        ])></span>
                        <span class="flex-1 truncate font-medium {{ $layoutActiveFarm?->id === $farm->id ? 'text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300' }}">{{ $farm->name }}</span>
                    </button>
                @empty
                    <p class="px-3 py-2 text-sm text-gray-400">Belum ada farm</p>
                @endforelse
                <a href="{{ route('settings.index') }}" class="mt-1 block border-t border-gray-100 px-3 py-2 text-xs font-semibold text-brand-600 transition hover:bg-gray-50 dark:border-gray-700 dark:text-brand-400 dark:hover:bg-gray-700/60">
                    + Kelola Farm
                </a>
            </div>
        </div>

        {{-- Dark mode toggle --}}
        <button
            x-data="Alpine.store('theme')"
            @click="toggle()"
            class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
            :aria-label="dark ? 'Mode terang' : 'Mode gelap'"
        >
            <x-icon name="moon" x-show="!dark" class="h-5 w-5" />
            <x-icon name="sun" x-show="dark" class="h-5 w-5" x-cloak />
        </button>

        {{-- Notification bell --}}
        <livewire:notification-bell />

        {{-- Profile dropdown --}}
        <div class="relative" x-data="{ profileOpen: false }" @click.outside="profileOpen = false">
            <button @click="profileOpen = !profileOpen" class="ms-1 flex items-center gap-2 rounded-lg p-1 transition hover:bg-gray-100 dark:hover:bg-gray-800" aria-label="Menu profil">
                <span class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-full bg-brand-100 ring-1 ring-gray-200 dark:bg-brand-950 dark:ring-gray-700">
                    @if (auth()->user()->avatar_path)
                        <img src="{{ asset('storage/'.auth()->user()->avatar_path) }}" alt="" class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full w-full items-center justify-center text-sm font-bold text-brand-700 dark:text-brand-300">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    @endif
                </span>
                <span class="hidden text-sm font-semibold text-gray-700 dark:text-gray-200 sm:block">{{ auth()->user()->name }}</span>
                <x-icon name="chevron-down" class="hidden h-3.5 w-3.5 text-gray-400 sm:block" />
            </button>
            <div
                x-show="profileOpen"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute end-0 z-50 mt-2 w-56 origin-top-right rounded-xl bg-white py-1.5 shadow-lg ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700"
                style="display: none"
            >
                <div class="border-b border-gray-100 px-4 py-2.5 dark:border-gray-700">
                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ auth()->user()->email }}</p>
                </div>
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-600 transition hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700/60">
                    <x-icon name="user" class="h-4 w-4 text-gray-400" /> Profil Saya
                </a>
                <a href="{{ route('alerts.settings') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-600 transition hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700/60">
                    <x-icon name="bell" class="h-4 w-4 text-gray-400" /> Preferensi Notifikasi
                </a>
                <form
                    method="POST"
                    action="{{ route('logout') }}"
                    class="border-t border-gray-100 pt-1 dark:border-gray-700"
                    x-data
                    @submit.prevent="window.Alpine.confirmModal('Keluar dari akun?', 'Anda akan diarahkan ke halaman login.', () => $el.submit())"
                >
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2.5 px-4 py-2 text-sm text-danger transition hover:bg-red-50 dark:hover:bg-red-950/40">
                        <x-icon name="logout" class="h-4 w-4" /> Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>