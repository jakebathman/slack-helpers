<?php

Route::get('/', function () {
    return view('welcome');
});

Route::get('in/{teamId?}', 'GetStaffIn')->name('in');
Route::any('slash/isin/{teamId?}', 'SlashIsIn')->name('slash.isin');

Route::get('oauth/redirect', 'OAuthController@redirect')->name('oauth.redirect');
