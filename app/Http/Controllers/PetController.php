<?php

namespace App\Http\Controllers;

use App\Http\Requests\PetRequest;
use App\Models\Pet;
use App\Services\PetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PetController extends Controller
{
    protected PetService $petService;

    public function __construct(PetService $petService)
    {
        $this->petService = $petService;
    }

    public function index(): JsonResponse
    {
        $pets = $this->petService->getPets();
        return response()->json($pets);
    }

    public function store(PetRequest $request): JsonResponse
    {
        $pet = $this->petService->savePets($request->validated());
        return response()->json($pet, 201);
    }

    public function show(Pet $pet): JsonResponse
    {
        $pet->load(['customer.user', 'breed.category']);
        return response()->json($pet);
    }

    public function update(PetRequest $request, Pet $pet): JsonResponse
    {
        $updated = $this->petService->updatePet($pet, $request->validated());
        return response()->json($updated);
    }

    public function destroy(Pet $pet): JsonResponse
    {
        $this->petService->deletePet($pet);
        return response()->json(null, 204);
    }
}

