<?php

namespace App\Http\Controllers;

use App\Actions\RevokeUserSessions;
use App\Http\Requests\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** Every user changes their own password; it is also where a temporary password must be replaced. */
class PasswordController extends Controller
{
    public function edit(Request $request): View
    {
        return view('password.edit', ['mustChange' => $request->user()->must_change_password]);
    }

    public function update(UpdatePasswordRequest $request, RevokeUserSessions $revokeUserSessions): RedirectResponse
    {
        $user = $request->user();
        $user->forceFill(['password' => $request->validated('password'), 'must_change_password' => false])->save();

        // Other devices are signed out; this session gets a new id and stays valid with the new password hash.
        $request->session()->regenerate();
        $revokeUserSessions->handle($user, exceptSessionId: $request->session()->getId());
        $request->session()->put('password_hash_'.Auth::getDefaultDriver(), Auth::guard()->hashPasswordForCookie($user->getAuthPassword()));

        return to_route('dashboard')->with('status', 'Senha alterada.');
    }
}
