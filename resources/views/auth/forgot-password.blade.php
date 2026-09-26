<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div>
        <h1 class="text-xl font-bold text-slate-900 dark:text-white">Lupa Kata Sandi</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Masukkan email yang terdaftar dan kami akan mengirimkan tautan untuk mengatur ulang kata sandi Anda.
        </p>
    </div>

    <form method="POST" action="{{ route('password.email') }}" class="mt-7 space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Email" />
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="nama@email.com" class="tt-input mt-1.5">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <button type="submit" class="tt-btn-primary w-full">Kirim Tautan Reset</button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
        Sudah ingat kata sandi?
        <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400">Masuk</a>
    </p>
</x-guest-layout>