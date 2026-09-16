<?php

namespace App\Livewire\Animals;

use App\Models\Animal;
use App\Models\Device;
use App\Models\Farm;
use App\Models\Fence;
use App\Services\ImageService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class AnimalForm extends Component
{
    use WithFileUploads;

    public const SPECIES = ['sapi', 'kambing', 'domba', 'kerbau', 'lainnya'];

    public const HEALTH_STATUS = ['sehat', 'sakit', 'hamil', 'karantina', 'lainnya'];

    public ?Animal $animal = null;

    public string $name = '';

    public string $tag_number = '';

    public string $species = 'sapi';

    public string $gender = 'jantan';

    public ?string $birth_date = null;

    public ?string $weight_kg = null;

    public string $health_status = 'sehat';

    public ?int $farm_id = null;

    public ?int $device_id = null;

    public ?string $notes = null;

    /** @var null|TemporaryUploadedFile */
    public $photo = null;

    /** @var array<int, int> */
    public array $selectedFences = [];

    public function mount(?Animal $animal = null): void
    {
        if ($animal !== null && $animal->exists) {
            Gate::authorize('update', $animal);

            $this->animal = $animal;
            $this->name = $animal->name;
            $this->tag_number = $animal->tag_number;
            $this->species = $animal->species;
            $this->gender = $animal->gender;
            $this->birth_date = $animal->birth_date?->toDateString();
            $this->weight_kg = $animal->weight_kg !== null ? (string) $animal->weight_kg : null;
            $this->health_status = $animal->health_status;
            $this->farm_id = $animal->farm_id;
            $this->device_id = $animal->device_id;
            $this->notes = $animal->notes;
            $this->selectedFences = $animal->fences()->pluck('fences.id')->map(fn ($id) => (int) $id)->all();
        } else {
            Gate::authorize('create', Animal::class);
            $this->tag_number = 'TT-NEW-'.strtoupper(substr(uniqid(), -6));
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'tag_number' => ['required', 'string', 'max:64', Rule::unique('animals', 'tag_number')->ignore($this->animal?->id)],
            'species' => ['required', 'in:'.implode(',', self::SPECIES)],
            'gender' => ['required', 'in:jantan,betina'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:3000'],
            'health_status' => ['required', 'in:'.implode(',', self::HEALTH_STATUS)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'farm_id' => ['nullable', 'exists:farms,id'],
            'device_id' => ['nullable', 'exists:devices,id'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'selectedFences' => ['array'],
            'selectedFences.*' => ['integer', 'exists:fences,id'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        $accessibleDeviceIds = Device::query()
            ->when(
                ! auth()->user()->isAdmin(),
                fn ($q) => $q->where('user_id', auth()->id())
            )
            ->pluck('id')
            ->all();

        if ($data['device_id'] !== null) {
            if (! in_array($data['device_id'], $accessibleDeviceIds, true)) {
                $this->addError('device_id', 'Perangkat tidak valid atau bukan milik Anda.');

                return;
            }

            $taken = Animal::query()
                ->where('device_id', $data['device_id'])
                ->when($this->animal !== null, fn ($q) => $q->whereKeyNot($this->animal->getKey()))
                ->exists();

            if ($taken) {
                $this->addError('device_id', 'Perangkat sudah terpasang pada hewan lain.');

                return;
            }
        }

        $accessibleFenceIds = Fence::query()
            ->when(
                ! auth()->user()->isAdmin(),
                fn ($q) => $q->where('user_id', auth()->id())
            )
            ->pluck('id')
            ->all();

        $invalidFences = array_diff($this->selectedFences, $accessibleFenceIds);

        if (count($invalidFences) > 0) {
            $this->addError('selectedFences', 'Beberapa pagar virtual yang dipilih tidak valid.');

            return;
        }

        if ($this->photo !== null) {
            $oldPhoto = $this->animal?->photo_path;
            $data['photo_path'] = ImageService::storeResized($this->photo, 'animals/photos');

            if ($oldPhoto !== null && $oldPhoto !== $data['photo_path']) {
                Storage::disk('public')->delete($oldPhoto);
            }
        }

        if ($this->animal !== null) {
            $this->animal->update($data);
            $animal = $this->animal;
            $this->assignFences($animal);

            session()->flash('status', 'Hewan "'.$animal->name.'" berhasil diperbarui.');

            $this->redirectRoute('animals.index');

            return;
        }

        $animal = Animal::create([
            ...$data,
            'user_id' => auth()->id(),
        ]);

        $this->assignFences($animal);

        session()->flash('status', 'Hewan "'.$animal->name.'" berhasil ditambahkan.');

        $this->redirectRoute('animals.index');
    }

    private function assignFences(Animal $animal): void
    {
        if (count($this->selectedFences) > 0) {
            $animal->fences()->syncWithPivotValues($this->selectedFences, ['assigned_at' => now()]);
        } else {
            $animal->fences()->sync([]);
        }
    }

    public function render()
    {
        $userId = auth()->user()->isAdmin() ? null : auth()->user()->id;

        $farms = Farm::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $fences = Fence::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->orderBy('name')
            ->get();

        $takenDeviceIds = Animal::query()
            ->whereNotNull('device_id')
            ->when($this->animal !== null, fn ($q) => $q->whereKeyNot($this->animal->getKey()))
            ->pluck('device_id');

        $devices = Device::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->whereNotIn('id', $takenDeviceIds)
            ->orderBy('name')
            ->get();

        $pageTitle = $this->animal !== null ? 'Edit Hewan' : 'Tambah Hewan';

        return view('livewire.animals.animal-form', [
            'farms' => $farms,
            'fences' => $fences,
            'devices' => $devices,
        ])->layout('layouts.app', [
            'pageTitle' => $pageTitle,
            'currentMenu' => ['label' => 'Hewan', 'route' => 'animals.index'],
        ]);
    }
}
