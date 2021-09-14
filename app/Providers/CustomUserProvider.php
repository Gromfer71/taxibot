<?php


namespace App\Providers;



use Illuminate\Auth\EloquentUserProvider as UserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;


class CustomUserProvider extends UserProvider
{
    public function validateCredentials(UserContract $user, array $credentials)
    {
        return $user->password == bcrypt($credentials['password']);
    }
}