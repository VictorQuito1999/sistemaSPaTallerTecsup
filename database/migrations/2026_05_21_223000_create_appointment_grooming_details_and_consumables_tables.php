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
        // 1. Añadir checklist_template a la tabla services
        if (Schema::hasTable('services') && !Schema::hasColumn('services', 'checklist_template')) {
            Schema::table('services', function (Blueprint $table) {
                $table->json('checklist_template')->nullable()->after('base_price');
            });
        }

        // 2. Crear la tabla appointment_grooming_details
        Schema::create('appointment_grooming_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained('appointments')->cascadeOnDelete();
            $table->json('checklist');
            $table->integer('actual_duration_min')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. Crear la tabla appointment_consumables
        Schema::create('appointment_consumables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity_used', 8, 2)->default(1.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_consumables');
        Schema::dropIfExists('appointment_grooming_details');
        
        if (Schema::hasTable('services') && Schema::hasColumn('services', 'checklist_template')) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropColumn('checklist_template');
            });
        }
    }
};
