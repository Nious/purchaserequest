<?php

use Illuminate\Support\Facades\Route;

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
    Route::get('/home', 'HomeController@index')
        ->name('home');

    Route::get('/budget-purchases/chart-data', 'HomeController@budgetPurchasesChart')
        ->name('budget.purchases.chart');
    
    Route::get('/home/total-budget-purchase', [HomeController::class, 'totalBudgetPurchase'])->name('home.totalBudgetPurchase');


    Route::get('/budgetbydepartmentchart', [App\Http\Controllers\HomeController::class, 'budgetByDepartmentChart'])
    ->name('home.budgetByDepartmentChart');


    Route::get('/current-month/chart-data', 'HomeController@currentMonthChart')
        ->name('current-month.chart');

});

