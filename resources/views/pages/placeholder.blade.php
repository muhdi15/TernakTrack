<x-app-layout>
    <div class="tt-card flex flex-col items-center justify-center gap-4 px-6 py-20 text-center">
        <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-950/60 dark:text-brand-300">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        </span>
        <div class="max-w-md space-y-1.5">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Modul dalam pengembangan</h2>
            <p class="text-sm leading-relaxed text-gray-500 dark:text-gray-400">{{ $description }}</p>
        </div>
        <a href="{{ route('dashboard') }}" class="tt-btn-primary mt-2">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Kembali ke Dashboard
        </a>
    </div>
</x-app-layout>