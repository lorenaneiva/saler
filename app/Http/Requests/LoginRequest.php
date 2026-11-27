<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $credentials = $this->validated();
        $key = 'login:'.Str::lower($credentials['email']).'|'.$this->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Muitas tentativas. Aguarde um minuto e tente novamente.',
            ]);
        }

        $inactive = false;
        $signedIn = Auth::attemptWhen($credentials, function (User $user) use (&$inactive): bool {
            $inactive = ! $user->ativo;

            return ! $inactive;
        });

        if (! $signedIn) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                // Only someone who typed the right password learns that the account exists but is disabled.
                'email' => $inactive ? 'Sua conta está desativada. Procure a pessoa responsável pelo sistema.' : 'E-mail ou senha inválidos.',
            ]);
        }

        RateLimiter::clear($key);
    }
}
