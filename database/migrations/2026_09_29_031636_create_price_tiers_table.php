<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_unit_id')->constrained()->cascadeOnDelete();
            $table->decimal('min_qty', 14, 3);
            $table->decimal('price_per_unit', 14, 2);
            $table->timestamps();

            $table->unique(['product_unit_id', 'min_qty']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_tiers');
    }
};
