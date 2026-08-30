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
        Schema::table('nbk_products', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('is_active');
        });

        DB::table('nbk_products')->where('is_active', false)->update(['status' => 'out_of_stock']);

        Schema::table('nbk_products', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nbk_products', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('sell_price');
        });

        DB::table('nbk_products')->where('status', '!=', 'active')->update(['is_active' => false]);

        Schema::table('nbk_products', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
