<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
  use App\Http\Controllers\Weather\WeatherController;

Route::get('/userdetail', function () {
    return "set is ready";
});

 Route::prefix('users')->controller(AuthController::class)->group(function () {
      Route::post('register', 'register')->middleware('throttle:5,1');
      Route::post('login', 'login')->middleware('throttle:10,1');

      Route::middleware('auth:api')->group(function () {
            Route::get('me', 'me');
            Route::post('logout', 'logout');
        });
  });


Route::get('/places', [WeatherController::class, 'searchPlaces']);
Route::get('/weather/coords', [WeatherController::class, 'showByCoords']);
Route::get('/weather/{city}', [WeatherController::class, 'show']);
Route::get('/forecast/coords', [WeatherController::class, 'forecastByCoords']);
Route::get('/forecast/{city}', [WeatherController::class, 'forecast']);
Route::get('/show-user-index/', [WeatherController::class, 'index']);
Route::get('/user-detail/{id}', [WeatherController::class, 'showdetail']);


