<?php

use App\Http\Controllers\OAuthController;
use App\Http\Controllers\SlashIsIn;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::any('slash/isin/{teamId?}', SlashIsIn::class)->name('slash.isin');

Route::get('oauth/redirect', [OAuthController::class, 'redirect'])->name('oauth.redirect');
