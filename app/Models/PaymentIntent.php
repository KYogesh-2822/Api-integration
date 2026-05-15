<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentIntent extends Model
{
    protected $table = 'payment_intents';
    protected $fillable = [
        'stripe_payment_intent_id',
        'amount',
        'currency',
        'status',
        'metadata',
    ];

}
