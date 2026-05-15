<?php 
use App\Http\Controllers\stripe\PaymentIntendController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\stripe\StripeWebhookController;

Route::get('/stripe-payment-intent', [PaymentIntendController::class, 'showForm'])->name('payment.form');
Route::post('/create-payment-intent', [PaymentIntendController::class, 'createIntent']);
Route::post('/save-payment', [PaymentIntendController::class, 'savePayment']);
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);