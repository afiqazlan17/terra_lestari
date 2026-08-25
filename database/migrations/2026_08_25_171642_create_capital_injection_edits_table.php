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
        Schema::create('capital_injection_edits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capital_injection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('edited_by')->constrained('users');
            $table->text('changes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capital_injection_edits');
    }
};
