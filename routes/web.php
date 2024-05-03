<?php

/*
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TestimonialController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\AdminController;
use App\Http\Middleware\AdminMiddleware;*/
use Illuminate\Support\Facades\Route;
use Gustocoder\LaravelDatatable\Http\Controllers\DatatableController;

/*Route::get('/', function () {
    return view('welcome');
});*/

Route::get('/chacha', [DatatableController::class, 'getChacha'])->name('chacha');

