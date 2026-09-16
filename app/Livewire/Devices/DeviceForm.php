<?php

namespace App\Livewire\Devices;

use App\Models\Device;
use App\Models\Farm;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class DeviceForm extends Component
{
    public ?Device $device = null;

    public string $name = '';

    public string $device_code = '';

    public string $status = 'active';

    public ?string $notes = null;

    public ?int $farm_id = null;

    public bool $showTokenModal = false;

    public ?string $createdToken = null;

    public function mount(?Device $device = null): void
    {
        if ($device !== null && $device->exists) {
            Gate::authorize('update', $device);

            $this->device = $device;
            $this->name = $device->name;
            $this->device_code = $device->device_code;
            $this->status = $device->status;
            $this->notes = $device->notes;
            $this->farm_id = $device->farm_id;
        } else {
            Gate::authorize('create', Device::class);
            $this->device_code = 'TT-'.strtoupper(Str::random(8));
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'device_code' => ['required', 'string', 'max:64', Rule::unique('devices', 'device_code')->ignore($this->device?->id)],
            'status' => ['required', 'in:active,inactive,maintenance,lost'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'farm_id' => ['nullable', 'exists:farms,id'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->device !== null) {
            $this->device->update($data);

            session()->flash('status', 'Perangkat "'.$this->device->name.'" berhasil diperbarui.');

            $this->redirectRoute('devices.index');

            return;
        }

        $created = Device::create([
            ...$data,
            'user_id' => auth()->id(),
            'api_token' => Str::random(64),
        ]);

        $this->createdToken = $created->api_token;
        $this->showTokenModal = true;
    }

    public function closeTokenModal(): void
    {
        $this->showTokenModal = false;
        $this->createdToken = null;

        $this->redirectRoute('devices.index');
    }

    public function render()
    {
        $userId = auth()->user()->isAdmin() ? null : auth()->user()->id;

        $farms = Farm::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $pageTitle = $this->device !== null ? 'Edit Perangkat' : 'Tambah Perangkat';

        return view('livewire.devices.device-form', ['farms' => $farms])
            ->layout('layouts.app', [
                'pageTitle' => $pageTitle,
                'currentMenu' => ['label' => 'Perangkat', 'route' => 'devices.index'],
            ]);
    }
}
