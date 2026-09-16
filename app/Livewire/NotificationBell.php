<?php

namespace App\Livewire;

use App\Models\Alert;
use Livewire\Component;

class NotificationBell extends Component
{
    public bool $open = false;

    public function mount(): void
    {
        //
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function markAsRead(int $id): void
    {
        $alert = Alert::query()
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if ($alert) {
            $alert->update(['is_read' => true, 'read_at' => now()]);
        }
    }

    public function markAllAsRead(): void
    {
        Alert::query()
            ->where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    public function render()
    {
        $userId = auth()->id();

        $recentAlerts = Alert::query()
            ->where('user_id', $userId)
            ->latest()
            ->limit(5)
            ->get();

        $unreadCount = Alert::query()
            ->where('user_id', $userId)
            ->where('is_read', false)
            ->count();

        return view('livewire.notification-bell', [
            'recentAlerts' => $recentAlerts,
            'unreadCount' => $unreadCount,
        ]);
    }
}
