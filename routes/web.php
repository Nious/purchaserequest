<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use Modules\Purchase\Http\Controllers\PurchaseController;
use Modules\Budget\Http\Controllers\MasterBudgetsController;
use Modules\TargetSale\Http\Controllers\TargetSalesController;
use Modules\Reports\Http\Controllers\ReportsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('auth.login');
})->middleware('guest');

Auth::routes(['register' => false]);

Route::group(['middleware' => 'auth'], function () {
    
    // --- DASHBOARD & CHARTS ---
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // Data untuk Cards (Total Budget & Purchase)
    Route::get('/home/total-budget-purchase', [HomeController::class, 'totalBudgetPurchase'])
        ->name('home.totalBudgetPurchase');

    // Data untuk Line Chart (Daily Budget vs Purchase)
    Route::get('/budget-purchases/chart-data', [HomeController::class, 'budgetPurchasesChart'])
        ->name('budget.purchases.chart');

    // Data untuk Doughnut Chart (Share per Department)
    Route::get('/budget-by-department/chart-data', [HomeController::class, 'budgetByDepartmentChart'])
        ->name('home.budgetByDepartmentChart');

    // Data untuk Mixed Chart (Target Sales vs Purchase) - BARU
    Route::get('/target-vs-purchase/chart-data', [HomeController::class, 'targetVsPurchaseChart'])
        ->name('home.targetVsPurchaseChart');

});