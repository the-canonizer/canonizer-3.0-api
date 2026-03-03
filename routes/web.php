<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', function () {
    return response()->json(['version' => app()->version()]);
});

Route::get('/key', function () {
    return Str::random(32);
});

// OAuth routes (Passport handles these, but we can define custom ones if needed)
// Route::post('/oauth/token', [\Dusterio\LumenPassport\Http\Controllers\AccessTokenController::class, 'issueToken']);
