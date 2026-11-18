<?php

use App\Enums\Role;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MarketPriceController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/harga', MarketPriceController::class)->name('prices.index');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    foreach (Role::cases() as $role) {
        Route::view($role->value.'/dashboard', 'role-dashboard', ['role' => $role])
            ->middleware('role:'.$role->value)
            ->name($role->value.'.dashboard');
    }
});

require __DIR__.'/settings.php';
