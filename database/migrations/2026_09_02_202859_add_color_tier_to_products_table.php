<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Overrides the category's automatic color-by-price tier - lets
            // same-priced items (e.g. two NBK dishes both at RM6.50) still
            // get visually distinct tile colors instead of collapsing into
            // one tier.
            $table->unsignedTinyInteger('color_tier')->nullable()->after('is_variable_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('color_tier');
        });
    }
};
