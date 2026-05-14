<?php

namespace App\Services;

use Laravel\Socialite\Facades\Socialite;

class SocialAuthService
{
public function redirectToProvider(string $provider)
{
    return Socialite::driver($provider)->redirect();
}
}