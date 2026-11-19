<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Keep the sync in the scheduler process to avoid a Command Prompt flash on Windows.
Schedule::call(fn (): bool => Artisan::call('pihps:sync') === 0)
    ->name('pihps:sync')
    ->weekdays()
    ->dailyAt('15:00')
    ->timezone('Asia/Makassar');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
