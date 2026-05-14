<?php

namespace App\Services;

use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Contracts\User as SocialUser;

class SocialAuthService
{


 /**
     * Redirect user to the provider's OAuth page.
     */
    public function redirectToProvider(string $provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle callback, find or create user, return User model.
     */
    public function handleProviderCallback(string $provider): User
    {
        $socialUser = Socialite::driver($provider)->user();

        return $this->findOrCreateUser($socialUser, $provider);
    }

    /**
     * Find existing user or create a new one.
     */
    private function findOrCreateUser(SocialUser $socialUser, string $provider): User
    {
        // 1. Already linked this social account
        $user = User::where('provider', $provider)
                    ->where('provider_id', $socialUser->getId())
                    ->first();

        if ($user) {
            $this->updateAvatarIfChanged($user, $socialUser->getAvatar());
            return $user;
        }

        // 2. Email exists → link social to existing account
        if ($socialUser->getEmail()) {
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                $user->update([
                    'provider'    => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar'      => $socialUser->getAvatar(),
                ]);
                return $user;
            }
        }

        // 3. Brand new user
        return User::create([
            'name'        => $socialUser->getName(),
            'email'       => $socialUser->getEmail(),
            'provider'    => $provider,
            'provider_id' => $socialUser->getId(),
            'avatar'      => $socialUser->getAvatar(),
        ]);
    }

    /**
     * Update avatar only if it changed.
     */
    private function updateAvatarIfChanged(User $user, ?string $avatar): void
    {
        if ($avatar && $user->avatar !== $avatar) {
            $user->update(['avatar' => $avatar]);
        }
    }

    /**
     * Log the user in.
     */
    public function loginUser(User $user): void
    {
        Auth::login($user);
    }

}