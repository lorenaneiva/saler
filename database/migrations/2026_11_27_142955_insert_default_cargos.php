<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data migration: the Root and Administrador roles are required for the system to work, so they ship with the schema
 * instead of depending on a seeder in production. Re-running it never duplicates or overwrites a role (unique slug).
 */
return new class extends Migration
{
    /** @var array<string, string> Role name by slug. */
    private const Cargos = ['root' => 'Root', 'administrador' => 'Administrador'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        DB::table('cargos')->insertOrIgnore(collect(self::Cargos)
            ->map(fn (string $name, string $slug): array => ['name' => $name, 'slug' => $slug, 'created_at' => $now, 'updated_at' => $now])
            ->values()
            ->all());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('cargos')->whereIn('slug', array_keys(self::Cargos))->delete();
    }
};
