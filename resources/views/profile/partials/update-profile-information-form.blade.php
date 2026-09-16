<section>
    <header>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        {{-- Avatar --}}
        <div class="flex items-center gap-5">
            <div class="relative h-20 w-20 shrink-0 overflow-hidden rounded-full bg-brand-100 ring-2 ring-brand-200 dark:bg-brand-950 dark:ring-brand-900">
                @if (auth()->user()->avatar_path)
                    <img id="avatar-preview" src="{{ asset('storage/'.auth()->user()->avatar_path) }}" alt="" class="h-full w-full object-cover">
                @else
                    <span id="avatar-preview" class="flex h-full w-full items-center justify-center text-2xl font-bold text-brand-700 dark:text-brand-300">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </span>
                @endif
            </div>
            <div class="min-w-0">
                <label for="avatar" class="tt-btn-secondary cursor-pointer">
                    {{ __('Ganti Foto') }}
                </label>
                <input id="avatar" name="avatar" type="file" accept="image/png,image/jpeg,image/webp" class="hidden"
                    x-data
                    @change="const f = $event.target.files[0]; if (f) { const r = new FileReader(); r.onload = e => { const el = document.getElementById('avatar-preview'); el.style.display = 'none'; const img = document.createElement('img'); img.className = 'h-full w-full object-cover'; img.src = e.target.result; el.parentElement.append(img); }; r.readAsDataURL(f); } else { location.reload(); }"
                >
                <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">PNG / JPG / WebP, maksimal 2 MB</p>
                <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
            </div>
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-300">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 dark:text-gray-400 dark:hover:text-white">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-brand-600 dark:text-brand-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <x-input-label for="phone" value="Nomor Telepon / WhatsApp" />
            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $user->phone)" placeholder="+62 812-3456-7890" autocomplete="tel" />
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-brand-600 dark:text-brand-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>

        <div class="flex items-center gap-2 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 dark:border-brand-900 dark:bg-brand-950/40">
            <x-icon name="bell" class="h-5 w-5 shrink-0 text-brand-600 dark:text-brand-400" />
            <p class="text-sm text-gray-700 dark:text-gray-300">
                Atur channel notifikasi (Email, Telegram, WhatsApp) di
                <a href="{{ route('alerts.settings') }}" class="font-semibold text-brand-600 underline hover:text-brand-700 dark:text-brand-400">Preferensi Notifikasi</a>.
            </p>
        </div>
    </form>
</section>