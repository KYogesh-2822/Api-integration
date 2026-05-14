<?php 
use App\Http\Controllers\social\SocialAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
    ->name('social.redirect')
    ->where('provider', 'google|facebook');

Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->where('provider', 'google|facebook');