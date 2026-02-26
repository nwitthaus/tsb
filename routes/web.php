<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('events/create', 'pages::events.create')->name('events.create');
    Route::livewire('events/{event}', 'pages::events.show')->name('events.show');
});

require __DIR__.'/settings.php';
