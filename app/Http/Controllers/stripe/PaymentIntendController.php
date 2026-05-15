<?php

namespace App\Http\Controllers\stripe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Payments\StripePaymentService; 
use App\Models\PaymentIntent;

class PaymentIntendController extends Controller
{

public function __construct(protected StripePaymentService $StripePaymentService){}
   public function showForm()
   {
       return view('home');
   }

   public function createIntent()
    {
        $paymentIntent = $this->StripePaymentService->createPaymentIntent(1000, 'usd');
        
        return response()->json([
            'clientSecret' => $paymentIntent->client_secret
        ]);
    }


     public function savePayment(Request $request)
        {
            $payment = PaymentIntent::create([
                'stripe_payment_intent_id' => $request->payment_intent_id,
                'amount' => $request->amount,
                'currency' => $request->currency,
                'status' => 'succeeded',
            ]);
            
            return response()->json(['success' => true]);
        }

}
