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
        Schema::create('breeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('species_categories'); // FK a la categoría
            $table->string('name', 150);
            $table->enum('size', ['extra_small', 'small', 'medium', 'large', 'extra_large']);
            $table->decimal('duration_factor', 3, 2)->default(1.00); // Para ajustar tiempo de cita uwu
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('breeds');
    }
};
