<?php

use Modules\Department\Http\Controllers\DepartmentsController;

Route::group(['middleware' => 'auth'], function () {
    Route::resource('departments', DepartmentsController::class);
});