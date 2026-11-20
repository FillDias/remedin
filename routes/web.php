<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('medications', 'pages::medications.index')->name('medications.index');
    Route::livewire('medications/create', 'pages::medications.form')->name('medications.create');
    Route::livewire('medications/{medication}/edit', 'pages::medications.form')->name('medications.edit');
});

require __DIR__.'/settings.php';
