<?php

namespace App\Livewire;

use App\Models\Alert;
use Livewire\Component;

class NotificationBadgeCount extends Component
{
    public int $count = 0;

    public function render()
    {
        $this->count = Alert::query()
            ->where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();

        return view('livewire.notification-badge-count');
    }
}
