<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - SPA (Vue 3)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('app');
});

Route::get('/login', function () {
    return view('app');
});

Route::get('/home', function () {
    return view('app');
});
Route::get('/dashboard', function () {
    return view('app');
});
Route::get('/dashboard/{path?}', function () {
    return view('app');
})->where('path', '.*');

Route::get('/register', function () {
    return view('app');
});
Route::get('/admin', function () {
    return view('app');
});
Route::get('/admin/{path?}', function () {
    return view('app');
})->where('path', '.*');
Route::get('/user', function () {
    return view('app');
});
Route::get('/user/{path?}', function () {
    return view('app');
})->where('path', '.*');
