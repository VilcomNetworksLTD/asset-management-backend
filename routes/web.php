<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\SSOController;

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');