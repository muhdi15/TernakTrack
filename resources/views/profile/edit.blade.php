<x-app-layout>
    <x-slot name="subtitle">
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola informasi akun, kata sandi, dan preferensi Anda.</p>
    </x-slot>

    <div class="space-y-6">
        <div class="tt-card p-4 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="tt-card p-4 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="tt-card p-4 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>