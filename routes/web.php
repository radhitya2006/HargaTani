<?php

use App\Enums\Role;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MarketPriceController;
use App\Http\Controllers\SellingPriceCalculatorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/harga', MarketPriceController::class)->name('prices.index');
Route::get('/kalkulator', [SellingPriceCalculatorController::class, 'show'])->name('calculator.show');
Route::post('/kalkulator', [SellingPriceCalculatorController::class, 'calculate'])->name('calculator.calculate');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', fn (Request $request) => $request->user()?->role === Role::Petani
        ? to_route('petani.dashboard')
        : view('dashboard'))->name('dashboard');
    foreach (Role::cases() as $role) {
        Route::view($role->value.'/dashboard', 'role-dashboard', ['role' => $role])
            ->middleware('role:'.$role->value)
            ->name($role->value.'.dashboard');
    }
});

require __DIR__.'/settings.php';
