<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white/95 backdrop-blur dark:border-gray-800 dark:bg-gray-900/95 lg:hidden" aria-label="Navigasi bawah">
    <div class="grid grid-cols-5">
        @php
            $bottomItems = [
                ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
                ['route' => 'map', 'label' => 'Peta', 'icon' => 'map'],
                ['route' => 'animals.index', 'label' => 'Hewan', 'icon' => 'tag'],
                ['route' => 'devices.index', 'label' => 'Perangkat', 'icon' => 'chip'],
            ];
        @endphp
        @foreach ($bottomItems as $item)
            @php $active = request()->routeIs($item['route']); @endphp
            <a
                href="{{ route($item['route']) }}"
                :class="drawerOpen ? '' : ''"
                @class([
                    'flex flex-col items-center gap-0.5 py-2.5 text-[10px] font-medium transition',
                    'text-brand-600 dark:text-brand-400' => $active,
                    'text-gray-500 dark:text-gray-400' => ! $active,
                ])
            >
                <x-icon :name="$item['icon']" class="h-5 w-5" />
                {{ $item['label'] }}
            </a>
        @endforeach
        <button @click="drawerOpen = true" class="flex flex-col items-center gap-0.5 py-2.5 text-[10px] font-medium text-gray-500 transition dark:text-gray-400" aria-label="Menu lainnya">
            <x-icon name="menu" class="h-5 w-5" />
            Menu
        </button>
    </div>
</nav>