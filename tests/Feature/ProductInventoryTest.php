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

class ProductInventoryTest extends TestCase
{
    use DatabaseTransactions;

    private function createProductFixture(float $stock = 5, float $minStock = 2, bool $basic = true): Product
    {
        $category = Category::first() ?? Category::create(['name' => 'Higiene']);
        $brand = Brand::first() ?? Brand::create(['name' => 'MarcaTest', 'origin' => 'Nacional']);
        $unit = Unit::first() ?? Unit::create(['name' => 'botella', 'abbreviation' => 'bot.']);

        $product = Product::create([
            'name' => 'Champú base spa',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'unit_id' => $unit->id,
            'sku' => 'SHP-BASE-'.uniqid(),
            'description' => 'Insumo básico',
            'price' => 12.50,
            'stock' => $stock,
            'min_stock' => $minStock,
            'stock_min' => $minStock,
            'stock_max' => 50,
            'is_basic_supply' => $basic,
            'is_active' => true,
        ]);

        Stock::create([
            'product_id' => $product->id,
            'current_quantity' => $stock,
        ]);

        return $product;
    }

    public function test_admin_can_crud_products(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $category = Category::first() ?? Category::create(['name' => 'Accesorios']);
        $brand = Brand::first() ?? Brand::create(['name' => 'BrandCRUD', 'origin' => 'Import']);
        $unit = Unit::first() ?? Unit::create(['name' => 'unidad', 'abbreviation' => 'und.']);

        $payload = [
            'name' => 'Jabón neutro 1L',
            'description' => 'Para baño express',
            'price' => 18.00,
            'stock' => 20,
            'min_stock' => 5,
            'sku' => 'JAB-001',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'unit_id' => $unit->id,
            'is_basic_supply' => false,
            'is_active' => true,
        ];

        $create = $this->postJson('/api/admin/products', $payload);
        $create->assertStatus(201)
            ->assertJsonPath('name', 'Jabón neutro 1L')
            ->assertJsonPath('stock', 20);

        $productId = $create->json('id');

        $this->getJson('/api/admin/products')
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $productId]);

        $this->putJson("/api/admin/products/{$productId}", array_merge($payload, [
            'stock' => 15,
            'name' => 'Jabón neutro 1L (actualizado)',
        ]))->assertStatus(200)
            ->assertJsonPath('stock', 15);

        $this->deleteJson("/api/admin/products/{$productId}")
            ->assertStatus(204);
    }

    public function test_finishing_appointment_without_supplies_deducts_basic_products(): void
    {
        $product = $this->createProductFixture(stock: 3, minStock: 2, basic: true);

        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user);

        $service = Service::create([
            'name' => 'Baño',
            'type' => 'grooming',
            'base_duration_min' => 60,
            'base_price' => 25,
            'is_active' => true,
        ]);

        $species = \App\Models\species_categories::first()
            ?? \App\Models\species_categories::create(['name' => 'Perro']);
        $breed = Breeds::first() ?? Breeds::create([
            'name' => 'Mestizo',
            'species_id' => $species->id,
            'duration_factor' => 1,
            'price_factor' => 1,
        ]);

        $employeeUser = User::factory()->create(['role' => 'groomer']);
        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'ci' => 'V-11111111',
            'specialty' => 'Groomer',
            'shift' => 'Mañana',
            'is_active' => true,
        ]);

        $customerUser = User::factory()->create(['role' => 'customer']);
        $customer = \App\Models\Customer::create([
            'user_id' => $customerUser->id,
            'ci' => '999',
            'status' => 1,
        ]);

        $pet = Pet::create([
            'customer_id' => $customer->id,
            'name' => 'Toby',
            'species_id' => $species->id,
            'breed_id' => $breed->id,
            'gender' => 'male',
            'birth_date' => '2021-01-01',
            'weight_kg' => 8,
        ]);

        $appointment = Appointment::create([
            'pet_id' => $pet->id,
            'employee_id' => $employee->id,
            'service_id' => $service->id,
            'appointment_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'agreed_price' => 25,
            'payment_type' => 'cash',
        ]);

        $response = $this->putJson("/api/appointments/{$appointment->id}", [
            'status' => 'finished',
            'payment_type' => 'cash',
        ]);

        $response->assertStatus(200);

        $product->refresh();
        $this->assertEquals(2.0, (float) $product->stock);

        $this->assertDatabaseHas('appointment_consumables', [
            'appointment_id' => $appointment->id,
            'product_id' => $product->id,
        ]);
    }
}
