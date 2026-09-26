<x-guest-layout>
    <div>
        <h1 class="text-xl font-bold text-slate-900 dark:text-white">Buat akun baru</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Mulai pantau ternak Anda dengan pagar virtual.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="mt-7 space-y-5">
        @csrf

        <div>
            <x-input-label for="name" value="Nama Lengkap" />
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Nama Anda" class="tt-input mt-1.5">
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" value="Email" />
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="nama@email.com" class="tt-input mt-1.5">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Kata Sandi" />
            <input id="password" type="password" name="password" required autocomplete="new-password" placeholder="••••••••" class="tt-input mt-1.5">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Konfirmasi Kata Sandi" />
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••" class="tt-input mt-1.5">
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <button type="submit" class="tt-btn-primary w-full">Daftar</button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400">Masuk</a>
    </p>
</x-guest-layout>