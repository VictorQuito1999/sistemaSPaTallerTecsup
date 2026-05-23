<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Breeds;
use App\Models\Employee;
use App\Models\Pet;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_create_payment_and_mark_appointment_as_paid(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $service = Service::create([
            'name' => 'Corte premium',
            'type' => 'grooming',
            'base_duration_min' => 60,
            'base_price' => 40,
            'is_active' => true,
        ]);

        $species = \App\Models\species_categories::first()
            ?? \App\Models\species_categories::create(['name' => 'Perro']);
        $breed = Breeds::first() ?? Breeds::create([
            'name' => 'Labrador',
            'species_id' => $species->id,
            'duration_factor' => 1,
            'price_factor' => 1,
        ]);

        $employeeUser = User::factory()->create(['role' => 'groomer']);
        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'ci' => 'V-22222222',
            'specialty' => 'Groomer',
            'shift' => 'Mañana',
            'is_active' => true,
        ]);

        $customerUser = User::factory()->create(['role' => 'customer']);
        $customer = \App\Models\Customer::create([
            'user_id' => $customerUser->id,
            'ci' => '888',
            'status' => 1,
        ]);

        $pet = Pet::create([
            'customer_id' => $customer->id,
            'name' => 'Luna',
            'species_id' => $species->id,
            'breed_id' => $breed->id,
            'gender' => 'female',
            'birth_date' => '2022-01-01',
            'weight_kg' => 20,
        ]);

        $appointment = Appointment::create([
            'pet_id' => $pet->id,
            'employee_id' => $employee->id,
            'service_id' => $service->id,
            'appointment_date' => now()->addDay()->toDateString(),
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'status' => 'confirmed',
            'agreed_price' => 40,
        ]);

        $response = $this->postJson('/api/payments', [
            'appointment_id' => $appointment->id,
            'total_amount' => 40.00,
            'payment_method' => 'Código QR (Yape/Plin/Simple)',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('payments', [
            'appointment_id' => $appointment->id,
            'total_amount' => 40.00,
            'payment_method' => 'Código QR (Yape/Plin/Simple)',
        ]);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'paid',
            'payment_type' => 'Código QR (Yape/Plin/Simple)',
        ]);
    }

    public function test_can_process_retail_only_payment_and_deduct_product_stock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        // Find or create Category, Brand, Unit
        $category = \App\Models\Category::first() ?? \App\Models\Category::create(['name' => 'Alimentos']);
        $brand = \App\Models\Brand::first() ?? \App\Models\Brand::create(['name' => 'SuperPet', 'origin' => 'Nacional']);
        $unit = \App\Models\Unit::first() ?? \App\Models\Unit::create(['name' => 'unidad', 'abbreviation' => 'und']);

        // Create product with stock
        $product = \App\Models\Product::create([
            'name' => 'Champú Antiparasitario',
            'sku' => 'CHMP-001',
            'description' => 'Champú antiparasitario para perros',
            'price' => 25.00,
            'stock' => 5,
            'min_stock' => 4, // Will trigger warning if stock drops to 3
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'unit_id' => $unit->id,
            'is_basic_supply' => false,
            'is_active' => true,
        ]);

        \App\Models\Stock::create([
            'product_id' => $product->id,
            'current_quantity' => 5,
        ]);

        $response = $this->postJson('/api/payments', [
            'appointment_id' => null,
            'total_amount' => 50.00,
            'payment_method' => 'Efectivo',
            'items' => [
                [
                    'productId' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'low_stock_alerts');

        $this->assertDatabaseHas('payments', [
            'appointment_id' => null,
            'total_amount' => 50.00,
            'payment_method' => 'Efectivo',
        ]);

        // Stock should be 3 now (5 - 2)
        $this->assertEquals(3, $product->fresh()->current_stock);
    }
}
