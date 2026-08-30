<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nbk_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nbk_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('nbk_product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->decimal('unit_cost', 10, 2);
            $table->unsignedInteger('qty_ordered');
            $table->decimal('sell_price', 10, 2);
            $table->decimal('buy_total', 12, 2);
            $table->decimal('sell_total', 12, 2);
            $table->decimal('profit', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nbk_order_items');
    }
};
