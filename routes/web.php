<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MarketPriceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SellingPriceCalculatorController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/harga', MarketPriceController::class)->name('prices.index');
Route::middleware('can:use-selling-price-calculator')->group(function () {
    Route::get('/kalkulator', [SellingPriceCalculatorController::class, 'show'])->name('calculator.show');
    Route::post('/kalkulator', [SellingPriceCalculatorController::class, 'calculate'])->name('calculator.calculate');
});
Route::get('/hasil-panen', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/hasil-panen/{product}', [CatalogController::class, 'show'])->whereNumber('product')->name('catalog.show');
Route::get('/hasil-panen/{product}/foto', [CatalogController::class, 'photo'])->whereNumber('product')->name('catalog.photo');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('admin/dashboard', [DashboardController::class, 'admin'])->middleware('role:admin')->name('admin.dashboard');
    Route::get('petani/dashboard', [DashboardController::class, 'petani'])->middleware('role:petani')->name('petani.dashboard');
    Route::get('pembeli/dashboard', [DashboardController::class, 'pembeli'])->middleware('role:pembeli')->name('pembeli.dashboard');

    Route::prefix('petani/produk')->name('petani.products.')->middleware('role:petani')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('tambah', [ProductController::class, 'create'])->name('create');
        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::get('{product}/edit', [ProductController::class, 'edit'])->name('edit');
        Route::patch('{product}', [ProductController::class, 'update'])->name('update');
        Route::patch('{product}/status', [ProductController::class, 'updateStatus'])->name('status');
        Route::get('{product}/foto', [ProductController::class, 'photo'])->name('photo');
    });
});

require __DIR__.'/settings.php';
