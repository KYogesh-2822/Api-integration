<?php
namespace App\Http\Controllers\social;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Services\SocialAuthService;


class SocialAuthController extends Controller
{

    public function __construct(private SocialAuthService $socialAuthService){}


    public function redirect($provider)
    {
        return $this->socialAuthService->redirectToProvider($provider);
    }
}
