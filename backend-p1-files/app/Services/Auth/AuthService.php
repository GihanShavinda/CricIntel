<?php

namespace App\Services\Auth;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthService
{
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'password' => $data['password'],
                'status' => UserStatus::Active,
            ]);

            $playerRole = Role::query()
                ->where('name', RoleName::Player->value)
                ->firstOrFail();

            $user->roles()->attach($playerRole);

            Auth::login($user);
            request()->session()->regenerate();

            return $user->load('roles');
        });
    }

    public function login(
        string $email,
        string $password,
        bool $remember = false
    ): User {
        $credentials = [
            'email' => strtolower($email),
            'password' => $password,
        ];

        if (! Auth::attempt($credentials, $remember)) {
            throw new AuthenticationException(
                'The provided credentials are incorrect.'
            );
        }

        request()->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        if ($user->status !== UserStatus::Active) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            throw new AuthenticationException(
                'This account is not active.'
            );
        }

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        return $user->load('roles');
    }

    public function logout(): void
    {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }
}
