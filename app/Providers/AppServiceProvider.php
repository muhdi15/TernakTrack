<?php

namespace App\Providers;

use App\Models\Animal;
use App\Models\Device;
use App\Models\Fence;
use App\Observers\AnimalObserver;
use App\Observers\DeviceObserver;
use App\Observers\FenceObserver;
use App\View\Composers\AppLayoutComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Device::observe(DeviceObserver::class);
        Animal::observe(AnimalObserver::class);
        Fence::observe(FenceObserver::class);

        View::composer('layouts.app', AppLayoutComposer::class);
    }
}
