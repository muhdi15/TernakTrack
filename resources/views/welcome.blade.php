<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#16a34a">

        <title>{{ config('app.name', 'TernakTrack') }} — Kelola Ternak &amp; Pagar Virtual Real-time</title>

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
    <body class="font-sans antialiased bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
        <div class="flex min-h-screen flex-col">
            {{-- ===== Navbar ===== --}}
            <header class="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-5">
                <a href="/" class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-white shadow-md shadow-brand-600/30">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-5.09-7-11a7 7 0 1 1 14 0c0 5.91-7 11-7 11Z" />
                            <circle cx="12" cy="10" r="2.5" fill="currentColor" stroke="none" />
                        </svg>
                    </span>
                    <span class="text-lg font-extrabold tracking-tight">
                        Ternak<span class="text-brand-600">Track</span>
                    </span>
                </a>

                <nav class="flex items-center gap-3">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="tt-btn-primary">
                            Buka Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="hidden text-sm font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white sm:inline">
                            Masuk
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="tt-btn-secondary">
                                Daftar
                            </a>
                        @endif
                    @endauth
                </nav>
            </header>

            {{-- ===== Hero ===== --}}
            <main class="mx-auto grid w-full max-w-6xl flex-1 items-center gap-12 px-6 py-12 lg:grid-cols-2 lg:gap-16">
                <div class="max-w-xl">
                    <span class="tt-badge mb-5">
                        Pemantauan ternak berbasis GPS &amp; pagar virtual
                    </span>

                    <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl">
                        Lacak ternak Anda dan jaga pagar virtual secara
                        <span class="text-brand-600">real-time</span>
                    </h1>

                    <p class="mt-5 text-lg leading-relaxed text-slate-600 dark:text-slate-300">
                        TernakTrack menerima data lokasi dari perangkat GPS molos ternak,
                        mengevaluasi posisi terhadap pagar virtual, dan memberi peringatan otomatis
                        saat ternak keluar zona — semuanya terpusat di satu dashboard.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="tt-btn-primary">
                                Buka Dashboard
                            </a>
                            <a href="{{ route('fences.index') }}" class="tt-btn-secondary">
                                Kelola Pagar
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="tt-btn-primary">
                                Masuk
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="tt-btn-secondary">
                                    Daftar Gratis
                                </a>
                            @endif
                        @endauth
                    </div>

                    <ul class="mt-8 flex flex-wrap gap-2">
                        @foreach (['Lacak real-time', 'Pagar virtual', 'Peringatan otomatis'] as $fitur)
                            <li class="inline-flex items-center gap-1.5 rounded-full bg-brand-100/80 px-3 py-1 text-xs font-semibold text-brand-800 dark:bg-brand-950 dark:text-brand-300">
                                <svg viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5">
                                    <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4l3.3 3.29 7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd" />
                                </svg>
                                {{ $fitur }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- ===== Ilustrasi mini-map ===== --}}
                <div class="relative">
                    <div class="aspect-square w-full max-w-md overflow-hidden rounded-3xl bg-white ring-1 ring-slate-200 shadow-card dark:bg-slate-900 dark:ring-slate-800">
                        <svg viewBox="0 0 400 400" class="h-full w-full" fill="none">
                            {{-- grid --}}
                            <g stroke="#e2e8f0" stroke-width="1" class="dark:stroke-slate-800">
                                <path d="M60 0V400M120 0V400M180 0V400M240 0V400M300 0V400M340 0V400" />
                                <path d="M0 60H400M0 120H400M0 180H400M0 240H400M0 300H400M0 340H400" />
                            </g>
                            {{-- pagar virtual --}}
                            <path
                                d="M90 260 L150 190 L260 180 L330 250 L265 330 L130 320 Z"
                                fill="#16a34a" fill-opacity="0.08"
                                stroke="#16a34a" stroke-width="3" stroke-dasharray="10 8" stroke-linejoin="round"
                            />
                            {{-- jejak hewan --}}
                            <path
                                d="M70 330 L110 300 L105 255 L150 225 L180 235 L235 205"
                                stroke="#94a3b8" stroke-width="2.5" stroke-linecap="round" stroke-dasharray="2 6"
                            />
                            {{-- pin di dalam --}}
                            <g>
                                <path d="M235 150c0 18-12 30-20 41-8-11-20-23-20-41a20 20 0 1 1 40 0Z" fill="#16a34a" fill-opacity="0.15" />
                                <circle cx="235" cy="142" r="11" fill="#16a34a" stroke="#fff" stroke-width="3.5" />
                            </g>
                            {{-- pin di luar zona --}}
                            <g>
                                <path d="M330 230c0 18-12 30-20 41-8-11-20-23-20-41a20 20 0 1 1 40 0Z" fill="#dc2626" fill-opacity="0.15" />
                                <circle cx="330" cy="222" r="11" fill="#dc2626" stroke="#fff" stroke-width="3.5" />
                            </g>
                            {{-- pin status tidak diterima --}}
                            <g>
                                <circle cx="120" cy="120" r="11" fill="#94a3b8" stroke="#fff" stroke-width="3.5" />
                            </g>
                            {{-- crosshair --}}
                            <circle cx="200" cy="200" r="52" stroke="#16a34a" stroke-width="1" stroke-dasharray="4 6" opacity="0.5" />
                        </svg>
                    </div>

                    <div class="tt-card absolute -left-3 top-6 rounded-xl px-3.5 py-2 shadow-card sm:-left-6">
                        <p class="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-200">
                            <span class="h-2 w-2 rounded-full bg-brand-500"></span>
                            3 hewan di dalam zona
                        </p>
                    </div>
                    <div class="tt-card absolute -right-2 bottom-10 rounded-xl px-3.5 py-2 shadow-card sm:-right-5">
                        <p class="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-200">
                            <span class="h-2 w-2 rounded-full bg-danger"></span>
                            Keluar zona!
                        </p>
                    </div>
                </div>
            </main>

            <footer class="mx-auto w-full max-w-6xl px-6 py-6 text-center text-xs text-slate-400 dark:text-slate-500">
                © {{ date('Y') }} {{ config('app.name', 'TernakTrack') }} · Pantau ternak Anda dari mana saja
            </footer>
        </div>
    </body>
</html>