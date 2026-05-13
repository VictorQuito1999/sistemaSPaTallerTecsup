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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pet_id')->constrained('pets');
            $table->foreignId('employee_id')->constrained('employees'); // Tu tabla actual
            $table->foreignId('service_id')->constrained('services');
            $table->date('appointment_date');
            $table->time('start_time');
            $table->time('end_time'); // Hora calculada con el factor
            $table->string('status')->default('pending'); // pending, confirmed, in_progress, completed, cancelled
            $table->decimal('agreed_price', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
