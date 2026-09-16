<div wire:poll.10s wire:key="dashboard-stats" class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-6">
    <div class="tt-card p-4 flex flex-col gap-1">
        <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Perangkat</span>
            <span class="text-gray-400 dark:text-gray-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/></svg>
            </span>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalDevices }}</p>
    </div>

    <div class="tt-card p-4 flex flex-col gap-1">
        <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Perangkat Aktif</span>
            <span class="text-brand-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25zm.75-12h9v9h-9v-9z"/></svg></span>
        </div>
        <p class="text-2xl font-bold text-brand-600 dark:text-brand-400">{{ $activeDevices }}</p>
    </div>

    <div class="tt-card p-4 flex flex-col gap-1">
        <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Perangkat Offline</span>
            <span class="text-danger"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M4.5 22h4l2.586-2.586a2 2 0 012.828 0L16.5 22h4m-6-11h-2m2 2.25V13.5a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6A2.25 2.25 0 014.5 3.75h5.25A2.25 2.25 0 0112 6v1.5M18.364 18.364l-2.829-2.829"/></svg></span>
        </div>
        <p class="text-2xl font-bold {{ $offlineDevices > 0 ? 'text-danger' : 'text-gray-900 dark:text-white' }}">{{ $offlineDevices }}</p>
    </div>

    <div class="tt-card p-4 flex flex-col gap-1">
        <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Hewan</span>
            <span class="text-gray-400 dark:text-gray-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z"/></svg></span>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalAnimals }}</p>
    </div>

    <div class="tt-card p-4 flex flex-col gap-1">
        <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Hewan di Luar Fence</span>
            <span class="text-warn-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg></span>
        </div>
        <p class="text-2xl font-bold {{ $animalsOutsideFence > 0 ? 'text-warn-500' : 'text-gray-900 dark:text-white' }}">{{ $animalsOutsideFence }}</p>
    </div>

    <div class="tt-card p-4 flex flex-col gap-1">
        <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Fence</span>
            <span class="text-gray-400 dark:text-gray-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg></span>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalFences }}</p>
    </div>
</div>