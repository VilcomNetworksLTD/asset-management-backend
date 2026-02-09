<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AssetController;

Route::apiResource('assets', AssetController::class);
