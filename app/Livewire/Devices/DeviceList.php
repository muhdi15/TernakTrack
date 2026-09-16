<?php

namespace App\Livewire\Devices;

use App\Models\Device;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class DeviceList extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $statusFilter = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $device = Device::findOrFail($id);

        Gate::authorize('delete', $device);

        $name = $device->name;
        $device->delete();

        session()->flash('status', 'Perangkat "'.$name.'" berhasil dihapus.');
    }

    public function render()
    {
        $userId = auth()->user()->isAdmin() ? null : auth()->user()->id;

        $devices = Device::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($this->search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('device_code', 'like', '%'.$this->search.'%')))
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->with('animal', 'farm')
            ->orderByDesc('last_seen_at')
            ->paginate(15);

        return view('livewire.devices.device-list', ['devices' => $devices])
            ->layout('layouts.app', [
                'pageTitle' => 'Perangkat (GPS Tracker)',
                'currentMenu' => ['label' => 'Perangkat', 'route' => 'devices.index'],
            ]);
    }
}
