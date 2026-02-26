<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');
    Route::livewire('events/create', 'pages::events.create')->name('events.create');
    Route::livewire('events/{event}/scoring', 'pages::events.scoring')->name('events.scoring');
    Route::livewire('events/{event}', 'pages::events.show')->name('events.show');
});

Route::livewire('{slug}', 'pages::scoreboard')->name('scoreboard')->middleware('throttle:60,1');

require __DIR__.'/settings.php';
