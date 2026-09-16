<?php

namespace App\Livewire\Animals;

use App\Models\Animal;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AnimalList extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $speciesFilter = '';

    #[Url(except: '')]
    public string $healthFilter = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSpeciesFilter(): void
    {
        $this->resetPage();
    }

    public function updatedHealthFilter(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $animal = Animal::findOrFail($id);

        Gate::authorize('delete', $animal);

        $name = $animal->name;
        $animal->delete();

        session()->flash('status', 'Hewan "'.$name.'" berhasil dihapus.');
    }

    public function render()
    {
        $userId = auth()->user()->isAdmin() ? null : auth()->user()->id;

        $animals = Animal::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($this->search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('tag_number', 'like', '%'.$this->search.'%')))
            ->when($this->speciesFilter !== '', fn ($q) => $q->where('species', $this->speciesFilter))
            ->when($this->healthFilter !== '', fn ($q) => $q->where('health_status', $this->healthFilter))
            ->with('device', 'farm')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.animals.animal-list', ['animals' => $animals])
            ->layout('layouts.app', [
                'pageTitle' => 'Hewan Ternak',
                'currentMenu' => ['label' => 'Hewan', 'route' => 'animals.index'],
            ]);
    }
}
