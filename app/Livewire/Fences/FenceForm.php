<?php

namespace App\Livewire\Fences;

use App\Models\Animal;
use App\Models\Farm;
use App\Models\Fence;
use App\Models\Setting;
use App\Services\GeofenceService;
use App\Services\PolygonValidator;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class FenceForm extends Component
{
    public ?Fence $fence = null;

    public string $name = '';

    public ?string $description = null;

    public string $color = '#22c55e';

    public string $fenceType = 'inclusion';

    public ?int $farmId = null;

    public bool $alertOnExit = true;

    public bool $alertOnEnter = false;

    /** @var array<int, int> */
    public array $selectedAnimals = [];

    /**
     * Polygon sebagai string JSON [{lat,lng}, ...] yang dikirim dari peta
     * (Leaflet.drag). Ini satu-satunya sumber kebenaran geometri dari frontend.
     */
    public string $polygon = '[]';

    public function mount(?Fence $fence = null): void
    {
        if ($fence !== null && $fence->exists) {
            Gate::authorize('update', $fence);

            $this->fence = $fence;
            $this->name = $fence->name;
            $this->description = $fence->description;
            $this->color = $fence->color;
            $this->fenceType = $fence->fence_type;
            $this->farmId = $fence->farm_id;
            $this->alertOnExit = $fence->alert_on_exit;
            $this->alertOnEnter = $fence->alert_on_enter;
            $this->selectedAnimals = $fence->animals()->pluck('animals.id')->map(fn ($id) => (int) $id)->all();
            $this->polygon = json_encode($fence->polygon_coordinates ?: [], JSON_UNESCAPED_SLASHES);
        } else {
            Gate::authorize('create', Fence::class);

            $this->color = Setting::where('key', 'default_fence_color')->value('value') ?? '#22c55e';
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{3,9}$/'],
            'fenceType' => ['required', 'in:inclusion,exclusion'],
            'farmId' => ['nullable', 'exists:farms,id'],
            'alertOnExit' => ['boolean'],
            'alertOnEnter' => ['boolean'],
            'selectedAnimals' => ['array'],
            'selectedAnimals.*' => ['integer', 'exists:animals,id'],
            'polygon' => ['required', 'string'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        $points = $this->polygonPoints();

        // Validasi geometri dulu — batasan ini tidak bisa dipenuhi lewat
        // validasi atribut biasa karena berupa JSON dari peta.
        $polygonErrors = PolygonValidator::validateForFence($points);

        if (count($polygonErrors) > 0) {
            $this->addError('polygon', implode(' ', $polygonErrors));

            return;
        }

        $userId = auth()->user()->isAdmin() ? null : auth()->user()->id;

        // Farm harus milik pengguna (atau admin: bebas).
        if ($data['farmId'] !== null) {
            $farmAllowed = Farm::query()
                ->when($userId, fn ($q) => $q->where('user_id', auth()->id()))
                ->whereKey($data['farmId'])
                ->exists();

            if (! $farmAllowed) {
                $this->addError('farmId', 'Lokasi tidak valid atau bukan milik Anda.');

                return;
            }
        }

        // Hewan yang dipilih harus dapat diakses pengguna.
        $accessibleAnimalIds = Animal::query()
            ->when($userId, fn ($q) => $q->where('user_id', auth()->id()))
            ->pluck('id')
            ->all();

        $invalidAnimals = array_diff($this->selectedAnimals, $accessibleAnimalIds);

        if (count($invalidAnimals) > 0) {
            $this->addError('selectedAnimals', 'Beberapa hewan yang dipilih tidak valid.');

            return;
        }

        $area = GeofenceService::calculateAreaHectares($points);

        $version = 1;

        if ($this->fence !== null) {
            $version = $this->fence->version;

            // Setiap perubahan bentuk polygon menaikkan versi fence.
            if (json_encode($this->fence->polygon_coordinates ?: [], JSON_UNESCAPED_SLASHES) !== $this->polygon) {
                $version++;
            }
        }

        $attributes = [
            'name' => $data['name'],
            'description' => $data['description'],
            'color' => $data['color'],
            'fence_type' => $data['fenceType'],
            'farm_id' => $data['farmId'],
            'alert_on_exit' => $data['alertOnExit'],
            'alert_on_enter' => $data['alertOnEnter'],
            'polygon_coordinates' => $points,
            'area_hectares' => $area,
            'version' => $version,
        ];

        if ($this->fence !== null) {
            $this->fence->update($attributes);
            $fence = $this->fence;
        } else {
            $fence = Fence::create([
                ...$attributes,
                'user_id' => auth()->id(),
            ]);
        }

        $this->assignAnimals($fence);

        session()->flash('status', 'Fence "'.$fence->name.'" berhasil disimpan.');

        $this->redirectRoute('fences.index');
    }

    /**
     * @return array<int, array{lat: float, lng: float}>
     */
    public function polygonPoints(): array
    {
        $decoded = json_decode($this->polygon, true);

        if (! is_array($decoded)) {
            return [];
        }

        $points = [];

        foreach ($decoded as $index => $point) {
            if (! is_array($point) || ! array_key_exists('lat', $point) || ! array_key_exists('lng', $point)) {
                continue;
            }

            $points[] = [
                'lat' => (float) $point['lat'],
                'lng' => (float) $point['lng'],
            ];
        }

        return $points;
    }

    /**
     * Simpan relasi hewan sambil mempertahankan timestamp assigned_at
     * untuk hewan yang sudah ter-assign sebelumnya.
     */
    private function assignAnimals(Fence $fence): void
    {
        $existing = $fence->animals()
            ->get()
            ->keyBy('id')
            ->map(fn ($animal) => $animal->pivot->assigned_at);

        $pivot = [];

        foreach ($this->selectedAnimals as $animalId) {
            $pivot[$animalId] = [
                'assigned_at' => $existing->get($animalId) ?? now(),
            ];
        }

        $fence->animals()->sync($pivot);
    }

    public function selectAllAnimals(): void
    {
        $this->selectedAnimals = Animal::query()
            ->when(
                ! auth()->user()->isAdmin(),
                fn ($q) => $q->where('user_id', auth()->id())
            )
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function clearAnimals(): void
    {
        $this->selectedAnimals = [];
    }

    public function render()
    {
        $userId = auth()->user()->isAdmin() ? null : auth()->user()->id;

        $farms = Farm::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->orderBy('name')
            ->get();

        $animals = Animal::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->orderBy('name')
            ->get();

        $points = $this->polygonPoints();

        $defaultCenter = ['lat' => -7.5, 'lng' => 110.2];

        if ($farms->isNotEmpty() && $farms->first()->latitude !== null && $farms->first()->longitude !== null) {
            $defaultCenter = [
                'lat' => (float) $farms->first()->latitude,
                'lng' => (float) $farms->first()->longitude,
            ];
        }

        if (count($points) >= 3) {
            $defaultCenter = [
                'lat' => collect($points)->avg('lat'),
                'lng' => collect($points)->avg('lng'),
            ];
        }

        return view('livewire.fences.fence-form', [
            'farms' => $farms,
            'animals' => $animals,
            'initialPoints' => $points,
            'defaultCenter' => $defaultCenter,
            'displayArea' => count($points) >= 3 ? GeofenceService::calculateAreaHectares($points) : null,
        ])->layout('layouts.app', [
            'pageTitle' => $this->fence !== null ? 'Edit Fence' : 'Buat Fence',
            'currentMenu' => ['label' => 'Fence', 'route' => 'fences.index'],
        ]);
    }
}
