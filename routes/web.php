<?php

use App\Enums\Role;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    foreach (Role::cases() as $role) {
        Route::view($role->value.'/dashboard', 'role-dashboard', ['role' => $role])
            ->middleware('role:'.$role->value)
            ->name($role->value.'.dashboard');
    }
});

require __DIR__.'/settings.php';
