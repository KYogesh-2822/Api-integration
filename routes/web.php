<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\stripe\PaymentIntendController;

Route::get('/', function () {
    return view('home');
});


Route::get('/userprofile', function () { return view('home'); });

Route::get('/userprofiles-detail', function () { return "Profile Page"; })->name('profile');