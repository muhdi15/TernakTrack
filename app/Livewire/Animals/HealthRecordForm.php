<?php

namespace App\Livewire\Animals;

use App\Models\Animal;
use App\Models\HealthRecord;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

class HealthRecordForm extends Component
{
    public const TYPES = ['pemeriksaan', 'vaksinasi', 'pengobatan', 'penimbangan', 'lainnya'];

    public Animal $animal;

    public bool $open = false;

    public ?int $recordId = null;

    public string $record_date = '';

    public string $type = 'pemeriksaan';

    public string $description = '';

    public ?string $vet_name = null;

    public ?string $next_due_date = null;

    public function mount(Animal $animal): void
    {
        $this->animal = $animal;
        $this->record_date = now()->toDateString();
    }

    #[On('create-health-record')]
    public function openCreate(): void
    {
        Gate::authorize('create', [HealthRecord::class, $this->animal]);

        $this->resetErrorBag();
        $this->recordId = null;
        $this->record_date = now()->toDateString();
        $this->type = 'pemeriksaan';
        $this->description = '';
        $this->vet_name = null;
        $this->next_due_date = null;
        $this->open = true;
    }

    #[On('edit-health-record')]
    public function openEdit(int $recordId): void
    {
        $record = HealthRecord::findOrFail($recordId);

        Gate::authorize('update', $record);

        $this->recordId = $record->id;
        $this->record_date = $record->record_date->toDateString();
        $this->type = $record->type;
        $this->description = $record->description;
        $this->vet_name = $record->vet_name;
        $this->next_due_date = $record->next_due_date?->toDateString();
        $this->open = true;
    }

    public function rules(): array
    {
        return [
            'record_date' => ['required', 'date'],
            'type' => ['required', 'in:'.implode(',', self::TYPES)],
            'description' => ['required', 'string', 'max:2000'],
            'vet_name' => ['nullable', 'string', 'max:150'],
            'next_due_date' => ['nullable', 'date', 'after_or_equal:record_date'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->recordId !== null) {
            $record = HealthRecord::findOrFail($this->recordId);

            Gate::authorize('update', $record);

            $record->update($data);
        } else {
            Gate::authorize('create', [HealthRecord::class, $this->animal]);

            $this->animal->healthRecords()->create($data);
        }

        $this->open = false;

        $this->dispatch('health-record-saved');
    }

    public function cancel(): void
    {
        $this->open = false;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.animals.health-record-form');
    }
}
