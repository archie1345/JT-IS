<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::inertia('/prototype', 'Prototype')->name('prototype');
// Route::inertia('/prototype/login', 'Login')->name('prototype.login');
Route::inertia('/dashboard', 'Dashboard')->name('dashboard');

require __DIR__.'/settings.php';
