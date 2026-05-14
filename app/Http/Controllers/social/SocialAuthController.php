<?php
namespace App\Http\Controllers\social;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Services\SocialAuthService;
use Illuminate\Http\RedirectResponse;

class SocialAuthController extends Controller
{

    public function __construct(private SocialAuthService $socialAuthService){}


    /**
     * Redirect to Google or Facebook.
     */
    public function redirect(string $provider): RedirectResponse
    {
        return $this->socialAuthService->redirectToProvider($provider);
    }

    /**
     * Handle the callback after login.
     */
    public function callback(string $provider): RedirectResponse
    {
        try {
            $user = $this->socialAuthService->handleProviderCallback($provider);
            $this->socialAuthService->loginUser($user);

            return redirect()->intended('/userprofile')
                ->with('success', 'Logged in successfully!');

        } catch (\Exception $e) {
            return redirect('/userprofile')
                ->with('error', 'Login failed: ' . $e->getMessage());
        }
    }

    public function dashboard(){
        return redirect()->route('/userprofile')->with('success', 'Login successfully');
    }

    public function logout()
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/userprofile')->with('success', 'Logged out successfully!');
    }


}
