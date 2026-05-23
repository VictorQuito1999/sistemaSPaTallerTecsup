<?php

namespace App\Services;

use App\Models\Pet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PetService
{
    public function getPets()
    {
        return Pet::with(['customer.user', 'breed.category'])->get();
    }

    public function getPetsByBreed($breed_id)
    {
        return Pet::where('breed_id', $breed_id)->paginate(20);
    }

    public function getPetsByAllergies($allergies)
    {
        return Pet::where('allergies', 'like', "%$allergies%")->paginate(20);
    }

    public function getPetsByOwner($customer_id)
    {
        return Pet::where('customer_id', $customer_id)->get();
    }

    public function savePets(array $data)
    {

        if (! isset($data['weight_kg']) && isset($data['size'])) {
            $data['weight_kg'] = match ($data['size']) {
                'small' => 5,
                'large' => 30,
                'giant' => 45,
                default => 15, // medio
            };
        }

        try {
            DB::beginTransaction();
            $pet = Pet::create([
                'customer_id' => $data['customer_id'],
                'breed_id' => $data['breed_id'],
                'name' => $data['name'],
                'gender' => $data['gender'],
                'birth_date' => $data['birth_date'] ?? null,
                'weight_kg' => $data['weight_kg'] ?? null,
                'allergies' => $data['allergies'] ?? null,
                'temperament' => $data['temperament'] ?? 'friendly',
                'is_active' => $data['is_active'] ?? true,
            ]);

            DB::commit();

            return $pet->load(['customer.user', 'breed.category']);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Error al guardar mascota: '.$e->getMessage());
            throw $e;
        }
    }

    public function updatePet(Pet $pet, array $data)
    {
        if (! isset($data['weight_kg']) && isset($data['size'])) {
            $data['weight_kg'] = match ($data['size']) {
                'small' => 5,
                'large' => 30,
                'giant' => 45,
                default => 15,
            };
        }
        $pet->update($data);

        return $pet->load(['customer.user', 'breed.category']);
    }

    public function deletePet(Pet $pet)
    {
        return $pet->delete();
    }
}
