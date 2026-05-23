<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Brand;
use App\Models\Breeds;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Pet;
use App\Models\Product;
use App\Models\Service;
use App\Models\Stock;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GroomingConsoleTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_retrieve_grooming_console_data(): void
    {
        $user = User::factory()->create(['role' => 'groomer']);
        $employee = Employee::create([
            'user_id' => $user->id,
            'ci' => 'V-12345678',
            'specialty' => 'Groomer',
            'shift' => 'Mañana',
            'is_active' => true,
        ]);

        $category = Category::first() ?? Category::create(['name' => 'Higiene']);
        $brand = Brand::first() ?? Brand::create(['name' => 'BrandTest', 'origin' => 'Nacional']);
        $unit = Unit::first() ?? Unit::create(['name' => 'botella', 'abbreviation' => 'bot.']);

        $product = Product::create([
            'name' => 'Shampoo hipoalergénico 500ml',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'unit_id' => $unit->id,
            'description' => 'Test desc',
            'stock' => 5.0,
            'stock_min' => 1.0,
            'stock_max' => 10.0,
            'is_active' => true,
        ]);

        $stock = Stock::create([
            'product_id' => $product->id,
            'current_quantity' => 5.0,
        ]);

        $service = Service::create([
            'name' => 'Baño completo',
            'type' => 'grooming',
            'base_duration_min' => 60,
            'base_price' => 30.00,
            'is_active' => true,
            'checklist_template' => [
                ['id' => 'c1', 'label' => 'Revisar piel', 'completed' => false],
            ]
        ]);

        $species = \App\Models\species_categories::first() ?? \App\Models\species_categories::create(['name' => 'Perro']);
        $breed = Breeds::first() ?? Breeds::create([
            'name' => 'Poodle',
            'species_id' => $species->id,
            'duration_factor' => 1.0,
            'price_factor' => 1.0,
        ]);

        $customerUser = User::factory()->create(['role' => 'customer']);
        $customer = \App\Models\Customer::create([
            'user_id' => $customerUser->id,
            'ci' => '123456',
            'status' => 1,
        ]);

        $pet = Pet::create([
            'customer_id' => $customer->id,
            'name' => 'Bobby',
            'species_id' => $species->id,
            'breed_id' => $breed->id,
            'gender' => 'male',
            'birth_date' => '2020-01-01',
            'weight_kg' => 12.0,
        ]);

        $appointment = Appointment::create([
            'pet_id' => $pet->id,
            'employee_id' => $employee->id,
            'service_id' => $service->id,
            'appointment_date' => '2026-05-22',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'agreed_price' => 30.00,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/appointments/{$appointment->id}/grooming-console");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'appointment_id',
                'pet_name',
                'service_name',
                'checklist',
                'supplies_catalog',
            ]);
    }

    public function test_can_finalize_grooming_and_deduct_stock(): void
    {
        $user = User::factory()->create(['role' => 'groomer']);
        $employee = Employee::create([
            'user_id' => $user->id,
            'ci' => 'V-87654321',
            'specialty' => 'Groomer',
            'shift' => 'Mañana',
            'is_active' => true,
        ]);

        $category = Category::first() ?? Category::create(['name' => 'Higiene']);
        $brand = Brand::first() ?? Brand::create(['name' => 'BrandTest', 'origin' => 'Nacional']);
        $unit = Unit::first() ?? Unit::create(['name' => 'botella', 'abbreviation' => 'bot.']);

        $product = Product::create([
            'name' => 'Shampoo hipoalergénico 500ml',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'unit_id' => $unit->id,
            'description' => 'Test desc',
            'stock' => 5.0,
            'stock_min' => 1.0,
            'stock_max' => 10.0,
            'is_active' => true,
        ]);

        $stock = Stock::create([
            'product_id' => $product->id,
            'current_quantity' => 5.00,
        ]);

        $service = Service::create([
            'name' => 'Baño completo',
            'type' => 'grooming',
            'base_duration_min' => 60,
            'base_price' => 30.00,
            'is_active' => true,
        ]);

        $species = \App\Models\species_categories::first() ?? \App\Models\species_categories::create(['name' => 'Perro']);
        $breed = Breeds::first() ?? Breeds::create([
            'name' => 'Poodle',
            'species_id' => $species->id,
            'duration_factor' => 1.0,
            'price_factor' => 1.0,
        ]);

        $customerUser = User::factory()->create(['role' => 'customer']);
        $customer = \App\Models\Customer::create([
            'user_id' => $customerUser->id,
            'ci' => '123456',
            'status' => 1,
        ]);

        $pet = Pet::create([
            'customer_id' => $customer->id,
            'name' => 'Bobby',
            'species_id' => $species->id,
            'breed_id' => $breed->id,
            'gender' => 'male',
            'birth_date' => '2020-01-01',
            'weight_kg' => 12.0,
        ]);

        $appointment = Appointment::create([
            'pet_id' => $pet->id,
            'employee_id' => $employee->id,
            'service_id' => $service->id,
            'appointment_date' => '2026-05-22',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'agreed_price' => 30.00,
        ]);

        Sanctum::actingAs($user);

        $payload = [
            'checklist' => [
                ['id' => 'c1', 'label' => 'Revisar piel', 'completed' => true]
            ],
            'actual_duration_min' => 45,
            'notes' => 'El perrito se portó excelente.',
            'supplies' => [$product->id],
        ];

        $response = $this->postJson("/api/appointments/{$appointment->id}/finish-grooming", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Verificar base de datos
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'finished',
        ]);

        $this->assertDatabaseHas('appointment_grooming_details', [
            'appointment_id' => $appointment->id,
            'actual_duration_min' => 45,
            'notes' => 'El perrito se portó excelente.',
        ]);

        $this->assertDatabaseHas('appointment_consumables', [
            'appointment_id' => $appointment->id,
            'product_id' => $product->id,
            'quantity_used' => 1.00,
        ]);

        // Verificar descuento de stock
        $this->assertDatabaseHas('inv_stocks', [
            'product_id' => $product->id,
            'current_quantity' => 4.00,
        ]);
    }
}
