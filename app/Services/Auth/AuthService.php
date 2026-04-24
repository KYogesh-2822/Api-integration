<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{

    /**
     * Register a new user and issue an access token.
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => strtolower($data['email']),
                'password' => Hash::make($data['password']),
            ]);

            $token = $user->createToken('api-access')->accessToken;

            return ['user' => $user, 'token' => $token];
        });
    }

    /**
     * Authenticate a user and issue an access token.
     *
     * @throws ValidationException
     */
    public function login(array $credentials): array
    {
        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user  = Auth::user();
        $token = $user->createToken('api-access')->accessToken;

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Revoke the current access token.
     */
    public function logout(User $user): void
    {
        $user->token()?->revoke();
    }
    

}
?>