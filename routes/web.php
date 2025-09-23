<?php

use App\Livewire\CreateNote;
use App\Livewire\DeviceDashboard;
use App\Livewire\DeviceInfo;
use App\Livewire\EditNote;
use App\Livewire\ShowNotes;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

Route::get('/notes', ShowNotes::class)
    ->middleware(['auth', 'verified'])
    ->name('notes.index');

Route::get('/notes/create', CreateNote::class)
    ->middleware(['auth', 'verified'])
    ->name('notes.create');

Route::get('/notes/edit/{note}', EditNote::class)
    ->middleware(['auth', 'verified'])
    ->name('notes.edit');

Route::get('/device-info', DeviceInfo::class)
    ->middleware(['auth', 'verified'])
    ->name('device-info');

Route::get('/device-dashboard', DeviceDashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('device-dashboard');
