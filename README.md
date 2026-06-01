<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Project Description

This application demonstrates social login and Stripe PaymentIntent integration.

- Users can log in using social media accounts via Google or Facebook.
- The app uses Stripe PaymentIntent for one-time payment processing.
- Payment flow is handled in the browser with Stripe Elements.
- The server stores payment status locally and updates it from Stripe webhooks.

## Payment Flow and Webhook Notes

This project includes Stripe payment integration using a payment intent flow and Stripe webhooks.

1. User visits the payment page:
   - `GET /stripe-payment-intent`
   - `App\Http\Controllers\stripe\PaymentIntendController@showForm`
   - returns `resources/views/home.blade.php`

2. Frontend requests a Stripe PaymentIntent:
   - `POST /create-payment-intent`
   - `PaymentIntendController@createIntent`
   - creates a Stripe PaymentIntent for $10.00 (1000 cents)
   - returns `clientSecret`

3. Stripe confirms payment in the browser:
   - JavaScript calls `stripe.confirmCardPayment(clientSecret, { payment_method: { card } })`
   - Stripe validates the card and processes the payment

4. Success path:
   - if Stripe returns `paymentIntent.status === 'succeeded'`
   - browser calls `POST /save-payment`
   - `PaymentIntendController@savePayment` stores the payment record locally
   - status is saved as `succeeded`

5. Failure path:
   - if Stripe returns an immediate error, the browser shows `Payment failed: ...`
   - no local save request is made

6. Webhook processing:
   - Stripe sends events to `POST /stripe/webhook`
   - handled by `App\Http\Controllers\stripe\StripeWebhookController@handleWebhook`
   - verifies signature using `services.stripe.webhook_secret`
   - updates local `payment_intents` record based on event type

7. Webhook events handled:
   - `payment_intent.created` → status `created`
   - `payment_intent.succeeded` → status `succeeded`
   - `payment_intent.payment_failed` → status `failed`

> Note: the webhook records Stripe payment status but does not automatically retry failed payments.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
