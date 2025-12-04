<?php

use Illuminate\Support\Facades\Route;
use Modules\TargetSale\Http\Controllers\TargetSalesController;

/*
|--------------------------------------------------------------------------
| Web Routes Target Sales
|--------------------------------------------------------------------------
|
| Definisi rute untuk modul Target Sales.
| Prefix URL: /target-sales
| Prefix Name: target_sales. (contoh: target_sales.index, target_sales.create)
|
*/

Route::prefix('target-sales')->name('target_sales.')->group(function () {
    Route::middleware(['web', 'auth'])->group(function () {
        
        // --- 1. RUTE STATIS (HARUS DI PALING ATAS) ---
        // Agar URL seperti /target-sales/pending tidak dianggap sebagai ID "pending"
        
        Route::get('/pending', [TargetSalesController::class, 'pending'])
             ->name('pending');

        Route::get('/create', [TargetSalesController::class, 'create'])
             ->name('create');

        // Route untuk menyimpan data (POST)
        Route::post('/', [TargetSalesController::class, 'store'])
             ->name('store');

        // Route Index (Daftar data) -> Ini yang dicari sidebar 'target_sales.index'
        Route::get('/', [TargetSalesController::class, 'index'])
             ->name('index');

        // --- 2. RUTE DINAMIS (HARUS DI BAWAH RUTE STATIS) ---
        // {id} akan menangkap angka/string apa saja
        
        Route::get('/{id}', [TargetSalesController::class, 'show'])
             ->name('show');

        Route::get('/{id}/edit', [TargetSalesController::class, 'edit'])
             ->name('edit');

        Route::put('/{id}', [TargetSalesController::class, 'update'])
             ->name('update');

        Route::delete('/{id}', [TargetSalesController::class, 'destroy'])
             ->name('destroy');

        // --- 3. RUTE APPROVAL ---
        
        Route::post('/{id}/approve', [TargetSalesController::class, 'approve'])
             ->name('approve');

        Route::post('/{id}/reject', [TargetSalesController::class, 'reject'])
             ->name('reject');
    });
});