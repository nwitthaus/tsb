<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');
    Route::livewire('events/create', 'pages::events.create')->name('events.create');
    Route::livewire('events/{event}/teams', 'pages::events.teams')->name('events.teams');
    Route::livewire('events/{event}/scoring', 'pages::events.scoring')->name('events.scoring');
    Route::livewire('events/{event}', 'pages::events.show')->name('events.show');

    Route::middleware('super-admin')->prefix('admin')->group(function () {
        Route::livewire('/', 'pages::admin.dashboard')->name('admin.dashboard');
        Route::livewire('users', 'pages::admin.users.index')->name('admin.users.index');
        Route::livewire('users/create', 'pages::admin.users.create')->name('admin.users.create');
        Route::livewire('users/{user}', 'pages::admin.users.edit')->name('admin.users.edit');
        Route::livewire('events', 'pages::admin.events.index')->name('admin.events.index');
        Route::livewire('events/create', 'pages::admin.events.create')->name('admin.events.create');
        Route::livewire('events/{event}', 'pages::admin.events.edit')->name('admin.events.edit');
    });
});

Route::livewire('{slug}', 'pages::scoreboard')->name('scoreboard')->middleware('throttle:60,1');

require __DIR__.'/settings.php';
