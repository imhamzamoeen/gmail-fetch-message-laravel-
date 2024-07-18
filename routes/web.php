<?php

use App\Http\Controllers\GmailController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::group(['as' => 'gmail.', 'prefix' => '/gmail'], function () {
    Route::get('/', [GmailController::class, 'listMessages']);

    Route::get('/redirect', [GmailController::class, 'redirectToProvider']);
    Route::get('/oauthCallback', [GmailController::class, 'callback']);
});

