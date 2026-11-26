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
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
            $table->boolean('has_had_products')->default(false);
        });

        DB::table('categories')
            ->whereExists(fn ($products) => $products->select(DB::raw(1))->from('products')->whereColumn('products.category_id', 'categories.id'))
            ->update(['has_had_products' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'has_had_products']);
        });
    }
};
