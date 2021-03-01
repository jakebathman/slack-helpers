<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('test', function (Request $request) {
    return $request->all();
});

Route::name('slack.')->prefix('slack')
    ->middleware(['slack.verify'])
    ->group(function () {
        Route::any('test', function () {
            return response()->json(['ok' => true]);
        })->name('test');

        Route::post('interaction', 'InteractionController')->name('interaction');
    });
