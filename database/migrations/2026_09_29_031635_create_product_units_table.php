<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('unit_name');
            $table->decimal('conversion_to_base', 14, 4);
            $table->timestamps();

            $table->unique(['product_id', 'unit_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_units');
    }
};
