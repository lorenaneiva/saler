<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->unique(['purchase_item_id', 'type']);
            $table->dropUnique(['purchase_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('stock_movements')->where('type', 'purchase_reversal')->exists()) {
            throw new RuntimeException('A migração não pode ser revertida após o registro de estornos.');
        }

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->unique('purchase_item_id');
            $table->dropUnique(['purchase_item_id', 'type']);
        });
    }
};
