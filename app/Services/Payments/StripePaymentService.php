<?php

namespace App\Services\Payments;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Log;

class StripePaymentService
{
    public function __construct()
    {
        // Set the Stripe API key from config
        Stripe::setApiKey(config('services.stripe.secret'));

        // Local dev workaround for certificate errors in XAMPP.
        if (app()->isLocal()) {
            Stripe::setVerifySslCerts(false);
        }
    }

    /**
     * Create a new PaymentIntent
     *
     * @param int $amount Amount in cents (or smallest currency unit)
     * @param string $currency Currency code (e.g., 'usd', 'eur')
     * @param array $metadata Optional metadata (e.g., order_id, user_id)
     * @return PaymentIntent
     */
    public function createPaymentIntent(int $amount, string $currency = 'usd', array $metadata = []): PaymentIntent
    {
        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount,
                'currency' => $currency,
                'metadata' => $metadata,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            return $paymentIntent;
        } catch (\Exception $e) {
            Log::error('Stripe PaymentIntent creation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Retrieve an existing PaymentIntent
     *
     * @param string $paymentIntentId
     * @return PaymentIntent
     */
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        try {
            return PaymentIntent::retrieve($paymentIntentId);
        } catch (\Exception $e) {
            Log::error('Stripe PaymentIntent retrieval failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing PaymentIntent
     *
     * @param string $paymentIntentId
     * @param array $data
     * @return PaymentIntent
     */
    public function updatePaymentIntent(string $paymentIntentId, array $data): PaymentIntent
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            return $paymentIntent->update($data);
        } catch (\Exception $e) {
            Log::error('Stripe PaymentIntent update failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Confirm a PaymentIntent (if needed for manual confirmation)
     *
     * @param string $paymentIntentId
     * @param string|null $paymentMethodId
     * @return PaymentIntent
     */
    public function confirmPaymentIntent(string $paymentIntentId, ?string $paymentMethodId = null): PaymentIntent
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            $params = [];
            if ($paymentMethodId) {
                $params['payment_method'] = $paymentMethodId;
            }
            return $paymentIntent->confirm($params);
        } catch (\Exception $e) {
            Log::error('Stripe PaymentIntent confirmation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cancel a PaymentIntent
     *
     * @param string $paymentIntentId
     * @return PaymentIntent
     */
    public function cancelPaymentIntent(string $paymentIntentId): PaymentIntent
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            return $paymentIntent->cancel();
        } catch (\Exception $e) {
            Log::error('Stripe PaymentIntent cancellation failed: ' . $e->getMessage());
            throw $e;
        }
    }
}