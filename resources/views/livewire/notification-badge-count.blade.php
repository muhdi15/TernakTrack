<span x-cloak wire:poll.15s>
    @if ($count > 0)
        <span class="inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-danger px-1 text-[10px] font-bold text-white">
            {{ $count > 99 ? '99+' : $count }}
        </span>
    @endif
</span>