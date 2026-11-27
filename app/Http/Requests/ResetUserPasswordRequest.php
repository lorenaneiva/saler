<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ResetUserPasswordRequest extends FormRequest
{
    /** Keeps these errors apart from the new user form on the same page. */
    protected $errorBag = 'resetPassword';

    public function authorize(): bool
    {
        return Gate::allows('manage-user', $this->route('user'));
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['password' => PasswordRules::rules()];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return PasswordRules::messages();
    }

    public function managedUser(): User
    {
        return $this->route('user');
    }
}
