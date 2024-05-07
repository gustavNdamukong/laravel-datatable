<?php

use Illuminate\Support\Facades\Route;
use Gustocoder\LaravelDatatable\Http\Controllers\ExampleController;

//This is an example of how you would define routes for the feature
Route::get('/users', [ExampleController::class, 'showUsers'])->name('show-users');

Route::get('/deleteUser/{userId}', [ExampleController::class, 'deleteUser'])->name('delete-user');

