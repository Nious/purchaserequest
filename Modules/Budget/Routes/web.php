<?php

use Illuminate\Support\Facades\Route;
use Modules\Budget\Http\Controllers\MasterBudgetsController;


Route::prefix('master_budget')->name('master_budget.')->group(function () {
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/pending', [MasterBudgetsController::class, 'pending'])->name('pending');

    Route::resource('master_budget', MasterBudgetsController::class);
    
    Route::get('/', [MasterBudgetsController::class, 'index'])->name('index');
    Route::get('/create', [MasterBudgetsController::class, 'create'])->name('create');
    Route::post('/', [MasterBudgetsController::class, 'store'])->name('store');
    Route::get('/{id}', [MasterBudgetsController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [MasterBudgetsController::class, 'edit'])->name('edit');
    Route::put('/{id}', [MasterBudgetsController::class, 'update'])->name('update');
    Route::delete('/{id}', [MasterBudgetsController::class, 'destroy'])->name('destroy');

    
    

    // Approval
    Route::post('/master_budget/{id}/update-status', [MasterBudgetsController::class, 'updateStatus'])
    ->name('master_budget.updateStatus');
    Route::post('/{id}/approve', [MasterBudgetsController::class, 'approve'])->name('approve');
    Route::post('/{id}/reject', [MasterBudgetsController::class, 'reject'])->name('reject');
});
});