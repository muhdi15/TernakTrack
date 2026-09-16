<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#16a34a">

        <title>{{ isset($pageTitle) && $pageTitle ? $pageTitle.' — ' : '' }}{{ config('app.name', 'TernakTrack') }}</title>

        {{-- Anti-FOUC: set dark class sebelum CSS --}}
        <script>
            try {
                const t = localStorage.getItem('tt_theme');
                if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        </script>

        <!-- Fonts: Plus Jakarta Sans -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        {{-- Sesi 5: Leaflet + Leaflet.Draw via CDN (global `window.L`) --}}
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css" />
        <script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>

        @livewireStyles
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body class="font-sans antialiased bg-slate-50 text-gray-900 dark:bg-slate-900 dark:text-gray-100">
        <div
            x-data="{ drawerOpen: false }"
            class="min-h-screen"
            @keydown.escape.window="drawerOpen = false"
        >
            {{-- ===== Loading bar ===== --}}
            <div
                x-data="Alpine.store('loading')"
                x-show="visible"
                class="fixed inset-x-0 top-0 z-[100] h-0.5 bg-transparent"
                x-cloak
            >
                <div class="h-full bg-brand-600 transition-all duration-300 ease-out" :style="`width: ${progress}%`"></div>
            </div>

            {{-- ===== Sidebar Desktop ===== --}}
            <aside class="fixed inset-y-0 start-0 z-40 hidden w-72 flex-col border-e border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 lg:flex">
                @include('layouts.partials.sidebar')
            </aside>

            {{-- ===== Sidebar Mobile Drawer ===== --}}
            <div x-cloak x-show="drawerOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="drawerOpen = false" class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden"></div>
            <div
                x-cloak
                x-show="drawerOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
                class="fixed inset-y-0 start-0 z-50 flex w-72 flex-col bg-white dark:bg-gray-900 lg:hidden"
            >
                @include('layouts.partials.sidebar', ['inDrawer' => true])
            </div>

            {{-- ===== Main area ===== --}}
            <div class="flex min-h-screen flex-col lg:ps-72">
                @include('layouts.partials.topbar')

                <main class="flex-1 pb-24 lg:pb-8">
                    <div class="mx-auto w-full max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                        {{-- Breadcrumb --}}
                        <nav class="mb-3 flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                            <a href="{{ route('dashboard') }}" class="transition hover:text-brand-600 dark:hover:text-brand-400">Home</a>
                            @if ($currentMenu)
                                <svg class="h-3.5 w-3.5 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                <span class="font-medium text-gray-700 dark:text-gray-200">{{ $currentMenu['label'] }}</span>
                            @endif
                        </nav>

                        {{-- Page header --}}
                        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h1 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-2xl">
                                    {{ $pageTitle ?? ($currentMenu['label'] ?? 'TernakTrack') }}
                                </h1>
                                @isset($subtitle)
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>
                                @endisset
                            </div>
                            @isset($actions)
                                <div class="flex items-center gap-2">
                                    {{ $actions }}
                                </div>
                            @endisset
                        </div>

                        @isset($header)
                            {{ $header }}
                        @endisset

                        {{-- Main content --}}
                        {{ $slot }}
                    </div>
                </main>
            </div>

            {{-- ===== Bottom nav (mobile) ===== --}}
            @include('layouts.partials.bottom-nav')

            {{-- ===== Confirm modal ===== --}}
            <div
                x-data="Alpine.store('confirm')"
                x-cloak
                x-show="open"
                class="fixed inset-0 z-[95] flex items-end justify-center p-4 sm:items-center"
                @keydown.escape.window="cancel()"
            >
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="cancel()" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative w-full max-w-md rounded-2xl bg-white p-5 shadow-xl dark:bg-gray-800"
                >
                    <div class="flex items-start gap-4">
                        <span :class="danger ? 'bg-red-100 text-danger dark:bg-red-950/50' : 'bg-brand-100 text-brand-600 dark:bg-brand-950/60'" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                        </span>
                        <div class="min-w-0 flex-1 pt-1">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white" x-text="title"></h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="message"></p>
                        </div>
                    </div>
                    <div class="mt-5 flex justify-end gap-2">
                        <button type="button" @click="cancel()" class="tt-btn-secondary">Batal</button>
                        <button type="button" @click="confirm()" :class="danger ? 'bg-danger hover:bg-red-700 focus-visible:outline-danger' : 'bg-brand-600 hover:bg-brand-700 focus-visible:outline-brand-600'" class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2" x-text="confirmLabel"></button>
                    </div>
                </div>
            </div>

            {{-- ===== Toasts ===== --}}
            <div
                x-data="Alpine.store('toast')"
                class="pointer-events-none fixed inset-x-0 bottom-20 z-[90] flex flex-col items-center gap-2 px-4 lg:bottom-5"
                x-cloak
            >
                <template x-for="item in items" :key="item.id">
                    <div
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl bg-white dark:bg-gray-800 px-4 py-3 shadow-lg ring-1 ring-gray-200 dark:ring-gray-700"
                    >
                        <span :class="{
                            'text-brand-500': item.type === 'success',
                            'text-danger': item.type === 'error',
                            'text-warn-500': item.type === 'warning',
                            'text-sky-500': item.type === 'info',
                        }">
                            <svg class="mt-0.5 h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="item.icons[item.type]"/></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="item.title"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="item.message"></p>
                        </div>
                        <button @click="items = items.filter(i => i.id !== item.id)" class="text-gray-400 transition hover:text-gray-600 dark:hover:text-gray-200">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </template>
            </div>
        </div>

        @livewireScripts
        @stack('scripts')

        {{-- FLASH MESSAGES -> TOAST --}}
        @php
            $flashStatus = session('status');
            $flashError = session('error');
            $flashAlerts = session('alerts', []);
        @endphp
        @if (! empty($flashStatus))
            <script>
                document.addEventListener('alpine:init', () => {
                    requestAnimationFrame(() => window.Alpine?.store('toast')?.success({!! json_encode($flashStatus, JSON_UNESCAPED_UNICODE) !!}));
                });
            </script>
        @endif
        @if (! empty($flashError))
            <script>
                document.addEventListener('alpine:init', () => {
                    requestAnimationFrame(() => window.Alpine?.store('toast')?.error({!! json_encode($flashError, JSON_UNESCAPED_UNICODE) !!}));
                });
            </script>
        @endif
        @if (! empty($flashAlerts))
            <script>
                document.addEventListener('alpine:init', () => {
                    requestAnimationFrame(() => {
                        @foreach ($flashAlerts as $alert)
                        window.Alpine?.store('toast')?.show({!! json_encode($alert['title'] ?? 'Notifikasi', JSON_UNESCAPED_UNICODE) !!}, {!! json_encode($alert['message'] ?? '', JSON_UNESCAPED_UNICODE) !!}, {!! json_encode($alert['type'] ?? 'info', JSON_UNESCAPED_UNICODE) !!});
                        @endforeach
                    });
                });
            </script>
        @endif
    </body>
</html>