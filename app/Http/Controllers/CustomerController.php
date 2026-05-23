<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    protected CustomerService $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    public function index(): JsonResponse
    {
        $customers = $this->customerService->getCustomers();
        
        // Formatear para que el frontend reciba full_name y otros campos esperados
        $formatted = $customers->map(function ($c) {
            return [
                'id' => $c->id,
                'first_name' => $c->user->first_name,
                'last_name' => $c->user->last_name,
                'full_name' => $c->user->first_name . ' ' . $c->user->last_name,
                'email' => $c->user->email,
                'phone' => $c->user->phone,
                'ci' => $c->ci,
                'status' => $c->status == 1 ? 'active' : 'blocked',
                'pets_count' => $c->pets->count(),
                'pets' => $c->pets->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'weight_kg' => $p->weight_kg,
                    ];
                }),
            ];
        });

        return response()->json($formatted);
    }

    public function store(CustomerRequest $request): JsonResponse
    {
        $customer = $this->customerService->createCustomer($request->validated());
        return response()->json($customer, 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        $customer->load(['user', 'pets.breed']);
        return response()->json($customer);
    }

    public function update(CustomerRequest $request, Customer $customer): JsonResponse
    {
        $updated = $this->customerService->updateCustomer($customer, $request->validated());
        return response()->json($updated);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $this->customerService->deleteCustomer($customer);
        return response()->json(null, 204);
    }
}
