<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

class CustomerService
{
    public function getCustomers()
    {
        return Customer::with(['user', 'pets'])->get();
    }

    public function getCustomerById($id)
    {
        return Customer::with(['user', 'pets.breed'])->findOrFail($id);
    }

    /**
     * Crea un cliente y su usuario asociado.
     */
    public function createCustomer(array $data)
    {
        try {
            DB::beginTransaction();

            // 1. Crear Usuario
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password'] ?? '12345678'), // Password temporal
                'role' => 'customer',
                'is_active' => true,
            ]);

            // 2. Crear Cliente
            $customer = Customer::create([
                'user_id' => $user->id,
                'ci' => $data['ci'] ?? null,
                'customer_group_id' => $data['customer_group_id'] ?? null,
                'status' => $data['status'] ?? 1, // 1 = Activo
            ]);

            DB::commit();

            return $customer->load('user');
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Error al crear cliente: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateCustomer(Customer $customer, array $data)
    {
        try {
            DB::beginTransaction();

            $customer->user->update([
                'first_name' => $data['first_name'] ?? $customer->user->first_name,
                'last_name' => $data['last_name'] ?? $customer->user->last_name,
                'email' => $data['email'] ?? $customer->user->email,
                'phone' => $data['phone'] ?? $customer->user->phone,
            ]);

            $customer->update([
                'ci' => $data['ci'] ?? $customer->ci,
                'customer_group_id' => $data['customer_group_id'] ?? $customer->customer_group_id,
                'status' => $data['status'] ?? $customer->status,
            ]);

            DB::commit();

            return $customer->load('user');
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Error al actualizar cliente: ' . $e->getMessage());
            throw $e;
        }
    }

    public function deleteCustomer(Customer $customer)
    {
        try {
            DB::beginTransaction();
            $user = $customer->user;
            $customer->delete();
            $user->delete();
            DB::commit();
            return true;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
