<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RevokeUserSessions
{
    /**
     * Signs the user out everywhere: deletes stored sessions (database driver) and rotates the "remember me" token.
     * Sessions in other drivers are still rejected on their next request by `AuthenticateSession` (changed password)
     * and `EnsureUserIsActive` (deactivated account).
     */
    public function handle(User $user, ?string $exceptSessionId = null): void
    {
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                ->where('user_id', $user->getKey())
                ->when($exceptSessionId, fn ($sessions) => $sessions->where('id', '!=', $exceptSessionId))
                ->delete();
        }

        $user->forceFill(['remember_token' => Str::random(60)])->saveQuietly();
    }
}
