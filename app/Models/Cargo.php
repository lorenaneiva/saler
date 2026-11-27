<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A user's role. Roles ship with the migrations (no management screen) and are identified by slug, never by id.
 */
class Cargo extends Model
{
    public const Root = 'root';

    public const Administrador = 'administrador';

    /** Roles that the Security module may assign; Root is only created through `app:create-user`. */
    public const AssignableInInterface = [self::Administrador];

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public static function idFor(string $slug): int
    {
        return static::query()->where('slug', $slug)->value('id')
            ?? throw new \RuntimeException("Cargo [{$slug}] não cadastrado; rode as migrations.");
    }
}
