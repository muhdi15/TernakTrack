<?php

namespace App\Providers;

use App\Models\Alert;
use App\Models\Animal;
use App\Models\Device;
use App\Models\Fence;
use App\Models\HealthRecord;
use App\Policies\AlertPolicy;
use App\Policies\AnimalPolicy;
use App\Policies\DevicePolicy;
use App\Policies\FencePolicy;
use App\Policies\HealthRecordPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Device::class => DevicePolicy::class,
        Animal::class => AnimalPolicy::class,
        Fence::class => FencePolicy::class,
        Alert::class => AlertPolicy::class,
        HealthRecord::class => HealthRecordPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
