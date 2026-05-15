<?php

namespace App\Http\Controllers\stripe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        
        $endpoint_secret = config('services.stripe.webhook_secret');
        
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        
        try {
            $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Webhook error: Invalid payload');
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Webhook error: Invalid signature');
            return response()->json(['error' => 'Invalid signature'], 400);
        }
        
        switch ($event->type) {
            case 'payment_intent.created':
                $paymentIntent = $event->data->object;
                $this->handlePaymentCreated($paymentIntent);
                break;

            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                $this->handlePaymentSuccess($paymentIntent);
                break;
                
            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                $this->handlePaymentFailure($paymentIntent);
                break;
                
            default:
                Log::info('Unhandled event type: ' . $event->type);
        }
        
        return response()->json(['status' => 'success']);
    }
    
    private function handlePaymentSuccess($paymentIntent)
    {
        PaymentIntent::updateOrCreate(
            ['stripe_payment_intent_id' => $paymentIntent->id],
            [
                'amount' => $paymentIntent->amount,
                'currency' => $paymentIntent->currency,
                'status' => 'succeeded',
                'metadata' => json_encode($paymentIntent->metadata)
            ]
        );
        
        Log::info('Payment succeeded: ' . $paymentIntent->id);
    }
    
    private function handlePaymentFailure($paymentIntent)
    {
        PaymentIntent::updateOrCreate(
            ['stripe_payment_intent_id' => $paymentIntent->id],
            ['status' => 'failed']
        );
        
        Log::warning('Payment failed: ' . $paymentIntent->id);
    }

    private function handlePaymentCreated($paymentIntent)
    {
        PaymentIntent::updateOrCreate(
            ['stripe_payment_intent_id' => $paymentIntent->id],
            [
                'amount' => $paymentIntent->amount,
                'currency' => $paymentIntent->currency,
                'status' => 'created',
                'metadata' => json_encode($paymentIntent->metadata),
            ]
        );

        Log::info('Payment created: ' . $paymentIntent->id);
    }
}
