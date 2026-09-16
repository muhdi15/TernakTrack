<?php

namespace App\View\Composers;

use App\Models\Farm;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class AppLayoutComposer
{
    public const MENU = [
        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
        ['route' => 'map', 'label' => 'Peta Real-time', 'icon' => 'map'],
        ['route' => 'animals.index', 'label' => 'Hewan', 'icon' => 'tag'],
        ['route' => 'devices.index', 'label' => 'Perangkat', 'icon' => 'chip'],
        ['route' => 'fences.index', 'label' => 'Fence', 'icon' => 'shield'],
        ['route' => 'calibration.index', 'label' => 'Kalibrasi', 'icon' => 'phone-mobile'],
        ['route' => 'logs.index', 'label' => 'Log', 'icon' => 'document'],
        ['route' => 'alerts.index', 'label' => 'Alert', 'icon' => 'bell'],
        ['route' => 'reports.index', 'label' => 'Laporan', 'icon' => 'chart'],
        ['route' => 'settings.index', 'label' => 'Pengaturan', 'icon' => 'cog'],
    ];

    public function compose(View $view): void
    {
        $user = auth()->user();

        $farms = collect();
        if ($user) {
            $farms = $user->isAdmin()
                ? Farm::query()->orderBy('name')->get()
                : $user->farms()->orderBy('name')->get();
        }

        $activeFarm = null;
        if ($farms->isNotEmpty()) {
            $activeFarm = $farms->firstWhere('id', Session::get('active_farm_id'))
                ?? $farms->first();
        }

        $routeName = Route::currentRouteName();
        $current = collect(self::MENU)->first(fn ($item) => $item['route'] === $routeName);

        $view->with('layoutFarms', $farms);
        $view->with('layoutActiveFarm', $activeFarm);
        $view->with('layoutMenu', collect(self::MENU));
        $view->with('currentMenu', $current);
        $view->with('currentRouteName', $routeName);
    }
}
