<?php

Route::get('/', function () {
    return view('welcome');
});

Route::get('in/{teamId?}', 'GetStaffIn')->name('slash.in');
Route::any('slash/isin/{teamId?}', 'SlashIsIn');

Route::get('oauth/redirect', 'OAuthController@redirect')->name('oauth.redirect');
