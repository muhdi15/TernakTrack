<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#16a34a">

        <title>{{ isset($pageTitle) && $pageTitle ? $pageTitle.' — ' : '' }}{{ config('app.name', 'TernakTrack') }}</title>

        <script>
            try {
                const t = localStorage.getItem('tt_theme');
                if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        </script>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-50 dark:bg-slate-950">
        <div class="flex min-h-screen flex-col items-center justify-start bg-gradient-to-b from-brand-50/80 via-slate-50 to-slate-50 px-4 py-12 dark:from-slate-900 dark:via-slate-950 dark:to-slate-950 sm:justify-center">
            {{-- ===== Brand ===== --}}
            <a href="/" class="group flex flex-col items-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-600 text-white shadow-lg shadow-brand-600/30 transition group-hover:bg-brand-700">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-7 w-7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-5.09-7-11a7 7 0 1 1 14 0c0 5.91-7 11-7 11Z" />
                        <circle cx="12" cy="10" r="2.5" fill="currentColor" stroke="none" />
                    </svg>
                </span>
                <span class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    Ternak<span class="text-brand-600">Track</span>
                </span>
            </a>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Pemantauan ternak berbasis GPS &amp; pagar virtual</p>

            {{-- ===== Kartu konten ===== --}}
            <div class="mt-8 w-full max-w-md">
                <div class="tt-card rounded-2xl p-8">
                    {{ $slot }}
                </div>
            </div>

            <p class="mt-8 text-center text-xs text-slate-400 dark:text-slate-500">
                © {{ date('Y') }} {{ config('app.name', 'TernakTrack') }} · Pantau ternak Anda dari mana saja
            </p>
        </div>
    </body>
</html>