<?php

use Illuminate\Support\Facades\Route;

// This catch-all route ensures that if you refresh on /dashboard (or any other
// client-side path) Laravel always returns the SPA entrypoint instead of a 404.
//
// NOTE: place this at the very end of web.php so it doesn't interfere with any
// other server-side routes. Static files under `public/` are served by the web
//server (Apache/Nginx) and won't hit this route, but if you see JS/CSS requests
//returning HTML on refresh then double-check your server config or asset paths.
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');