<?php

namespace App\Contracts;

interface PaymentGatewayInterface
{
    public function createPayment(array $data): array;

    public function retrievePayment(string $paymentId): array;
}