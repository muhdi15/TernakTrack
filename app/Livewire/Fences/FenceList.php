<?php

namespace App\Livewire\Fences;

use App\Models\Farm;
use App\Models\Fence;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;

class FenceList extends Component
{
    #[Url(except: '')]
    public string $fenceType = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $farmId = '';

    public function toggleActive(int $id): void
    {
        $fence = Fence::withTrashed()->findOrFail($id);

        Gate::authorize('update', $fence);

        $fence->update(['is_active' => ! $fence->is_active]);
    }

    public function delete(int $id): void
    {
        $fence = Fence::findOrFail($id);

        Gate::authorize('delete', $fence);

        $name = $fence->name;
        $fence->delete();

        session()->flash('status', 'Fence "'.$name.'" berhasil dihapus.');
    }

    public function render()
    {
        $userId = auth()->user()->isAdmin() ? null : auth()->user()->id;

        $fences = Fence::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($this->fenceType !== '', fn ($q) => $q->where('fence_type', $this->fenceType))
            ->when($this->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($this->farmId !== '', fn ($q) => $q->where('farm_id', (int) $this->farmId))
            ->withCount('animals')
            ->with('farm')
            ->orderByDesc('created_at')
            ->get();

        $farms = Farm::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->orderBy('name')
            ->get();

        return view('livewire.fences.fence-list', [
            'fences' => $fences,
            'farms' => $farms,
        ])->layout('layouts.app', [
            'pageTitle' => 'Virtual Fence',
            'currentMenu' => ['label' => 'Fence', 'route' => 'fences.index'],
        ]);
    }
}
