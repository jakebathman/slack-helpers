<?php

Route::get('/', function () {
    return view('welcome');
});

Route::any('slash/isin/{teamId?}', 'SlashIsIn')->name('slash.isin');

Route::get('oauth/redirect', 'OAuthController@redirect')->name('oauth.redirect');
