<?php

use App\Http\Controllers\FarmController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Livewire\Animals\AnimalDetail;
use App\Livewire\Animals\AnimalForm;
use App\Livewire\Animals\AnimalList;
use App\Livewire\Devices\DeviceDetail;
use App\Livewire\Devices\DeviceForm;
use App\Livewire\Devices\DeviceList;
use App\Livewire\Fences\FenceDetail;
use App\Livewire\Fences\FenceForm;
use App\Livewire\Fences\FenceList;
use App\Livewire\Map\LiveMap;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::post('/farm/select', [FarmController::class, 'select'])
        ->name('farm.select');

    // Modul yang akan diisi pada sesi berikutnya
    Route::get('/map', LiveMap::class)->name('map');

    Route::get('/animals', AnimalList::class)->name('animals.index');
    Route::get('/animals/create', AnimalForm::class)->name('animals.create');
    Route::get('/animals/{animal}', AnimalDetail::class)->name('animals.show');
    Route::get('/animals/{animal}/edit', AnimalForm::class)->name('animals.edit');

    Route::get('/devices', DeviceList::class)->name('devices.index');
    Route::get('/devices/create', DeviceForm::class)->name('devices.create');
    Route::get('/devices/{device}', DeviceDetail::class)->name('devices.show');
    Route::get('/devices/{device}/edit', DeviceForm::class)->name('devices.edit');

    Route::get('/fences', FenceList::class)->name('fences.index');
    Route::get('/fences/create', FenceForm::class)->name('fences.create');
    Route::get('/fences/{fence}', FenceDetail::class)->name('fences.show');
    Route::get('/fences/{fence}/edit', FenceForm::class)->name('fences.edit');

    Route::get('/calibration', [PageController::class, 'calibration'])->name('calibration.index');
    Route::get('/logs', [PageController::class, 'logs'])->name('logs.index');
    Route::get('/alerts', [PageController::class, 'alerts'])->name('alerts.index');
    Route::get('/alerts/settings', [PageController::class, 'alertSettings'])->name('alerts.settings');
    Route::get('/reports', [PageController::class, 'reports'])->name('reports.index');
    Route::get('/settings', [PageController::class, 'settings'])->name('settings.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
