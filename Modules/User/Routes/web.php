<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::group(['middleware' => 'auth'], function () {

    //User Profile
    Route::get('/user/profile', 'ProfileController@edit')->name('profile.edit');
    Route::patch('/user/profile', 'ProfileController@update')->name('profile.update');
    Route::patch('/user/password', 'ProfileController@updatePassword')->name('profile.update.password');
    // Route::post('/filepond/upload', [\Modules\Upload\Http\Controllers\FilepondController::class, 'upload'])->name('filepond.upload');

    //Users
    Route::resource('users', 'UsersController')->except('show');

    //Roles
    Route::resource('roles', 'RolesController')->except('show');

});
