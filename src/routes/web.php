<?php 
use Illuminate\Support\Facades\Route;
use Sahdev\SSO\Http\Controllers\SSOController;

Route::prefix('sso')->group(function () {
    Route::get('{provider}/redirect', [SSOController::class, 'redirect']);
    Route::get('{provider}/callback', [SSOController::class, 'callback']);
});