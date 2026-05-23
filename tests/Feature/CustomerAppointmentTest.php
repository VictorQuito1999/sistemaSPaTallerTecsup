<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Breeds;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerAppointmentTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_can_request_appointment_successfully(): void
    {
        $customerUser = User::factory()->create(['role' => 'customer']);
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'ci' => '12345678',
            'status' => 1,
        ]);

        $employeeUser = User::factory()->create(['role' => 'groomer']);
        $employee = \App\Models\Employee::create([
            'user_id' => $employeeUser->id,
            'ci' => 'V-33333333',
            'specialty' => 'Groomer',
            'shift' => 'Mañana',
            'is_active' => true,
        ]);

        Sanctum::actingAs($customerUser);

        $species = \App\Models\species_categories::first()
            ?? \App\Models\species_categories::create(['name' => 'Perro']);
        $breed = Breeds::first() ?? Breeds::create([
            'name' => 'Golden',
            'species_id' => $species->id,
            'duration_factor' => 1,
            'price_factor' => 1,
        ]);

        $pet = Pet::create([
            'customer_id' => $customer->id,
            'name' => 'Toby',
            'species_id' => $species->id,
            'breed_id' => $breed->id,
            'gender' => 'male',
            'birth_date' => '2023-01-01',
            'weight_kg' => 12,
        ]);

        $service = Service::create([
            'name' => 'Baño simple',
            'type' => 'bath',
            'base_duration_min' => 45,
            'base_price' => 20,
            'is_active' => true,
        ]);

        // Next Monday at 10:00 AM
        $nextMonday = now()->next('Monday')->setTime(10, 0)->toDateTimeString();

        $response = $this->postJson('/api/customer/appointments/request', [
            'pet_id' => $pet->id,
            'service_id' => $service->id,
            'start_time' => $nextMonday,
            'notes' => 'Tímido con los extraños',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('appointments', [
            'pet_id' => $pet->id,
            'service_id' => $service->id,
            'status' => 'pending',
            'notes' => 'Tímido con los extraños',
            'employee_id' => $employee->id,
        ]);
    }

    public function test_customer_cannot_request_appointment_for_foreign_pet(): void
    {
        $customerUser = User::factory()->create(['role' => 'customer']);
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'ci' => '12345678',
            'status' => 1,
        ]);

        $otherUser = User::factory()->create(['role' => 'customer']);
        $otherCustomer = Customer::create([
            'user_id' => $otherUser->id,
            'ci' => '87654321',
            'status' => 1,
        ]);

        $employeeUser = User::factory()->create(['role' => 'groomer']);
        \App\Models\Employee::create([
            'user_id' => $employeeUser->id,
            'ci' => 'V-44444444',
            'specialty' => 'Groomer',
            'shift' => 'Mañana',
            'is_active' => true,
        ]);

        Sanctum::actingAs($customerUser);

        $species = \App\Models\species_categories::first()
            ?? \App\Models\species_categories::create(['name' => 'Perro']);
        $breed = Breeds::first() ?? Breeds::create([
            'name' => 'Golden',
            'species_id' => $species->id,
            'duration_factor' => 1,
            'price_factor' => 1,
        ]);

        $foreignPet = Pet::create([
            'customer_id' => $otherCustomer->id,
            'name' => 'Max',
            'species_id' => $species->id,
            'breed_id' => $breed->id,
            'gender' => 'male',
            'birth_date' => '2023-01-01',
            'weight_kg' => 12,
        ]);

        $service = Service::create([
            'name' => 'Baño simple',
            'type' => 'bath',
            'base_duration_min' => 45,
            'base_price' => 20,
            'is_active' => true,
        ]);

        $nextMonday = now()->next('Monday')->setTime(10, 0)->toDateTimeString();

        $response = $this->postJson('/api/customer/appointments/request', [
            'pet_id' => $foreignPet->id,
            'service_id' => $service->id,
            'start_time' => $nextMonday,
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }
}
