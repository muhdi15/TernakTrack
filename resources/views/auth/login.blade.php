<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div>
        <h1 class="text-xl font-bold text-slate-900 dark:text-white">Selamat datang kembali</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Masuk untuk melanjutkan memantau ternak Anda.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="mt-7 space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Email" />
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="nama@email.com" class="tt-input mt-1.5">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" value="Kata Sandi" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">
                        Lupa kata sandi?
                    </a>
                @endif
            </div>
            <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" class="tt-input mt-1.5">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
            <input id="remember_me" type="checkbox" name="remember" class="rounded border-gray-300 text-brand-600 shadow-sm focus:ring-brand-500">
            Ingat saya
        </label>

        <button type="submit" class="tt-btn-primary w-full">Masuk</button>
    </form>

    @if (Route::has('register'))
        <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
            Belum punya akun?
            <a href="{{ route('register') }}" class="font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400">Daftar</a>
        </p>
    @endif
</x-guest-layout>