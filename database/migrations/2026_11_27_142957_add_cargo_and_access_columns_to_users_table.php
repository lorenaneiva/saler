<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gives every user exactly one role. Users that already exist keep all their data and become Administrador
 * (never Root), so they keep access to every commercial and operational feature they used before.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('cargo_id')->nullable()->constrained('cargos')->restrictOnDelete();
            $table->boolean('ativo')->default(true);
            $table->boolean('must_change_password')->default(false);
            $table->timestamp('last_login_at')->nullable();
        });

        $administrador = DB::table('cargos')->where('slug', 'administrador')->value('id');
        $root = DB::table('cargos')->where('slug', 'root')->value('id');
        if ($administrador === null || $root === null) {
            throw new RuntimeException('Os cargos Root e Administrador precisam existir antes desta migration.');
        }

        DB::table('users')->whereNull('cargo_id')->update(['cargo_id' => $administrador]);

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('cargo_id')->nullable(false)->change();
        });

        // At most one Root, enforced by the database so concurrent creations cannot both succeed.
        DB::statement('CREATE UNIQUE INDEX users_single_root_unique ON users (cargo_id) WHERE cargo_id = '.(int) $root);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_single_root_unique');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cargo_id');
            $table->dropColumn(['ativo', 'must_change_password', 'last_login_at']);
        });
    }
};
