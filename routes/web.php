<?php

use Illuminate\Support\Facades\Route;

// This catch-all route ensures that if you refresh on /dashboard, 
// Laravel loads the Vue app instead of a 404 error.
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');