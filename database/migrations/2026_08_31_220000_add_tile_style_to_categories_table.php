<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('tile_shape')->default('rectangle')->after('sort_order');
            $table->boolean('color_by_price')->default(false)->after('tile_shape');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['tile_shape', 'color_by_price']);
        });
    }
};
