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
        Schema::create('inv_stocks', function (Blueprint $table) {
            $table->id();
            // Relación con el producto
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            // Cantidades par ami stockk
            $table->decimal('current_quantity', 12, 2)->default(0.00);
            $table->timestamp('last_update')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inv_stocks');
    }
};
