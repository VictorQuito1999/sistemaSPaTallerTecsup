<?php

namespace App\Http\Controllers;

use App\Services\PetHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PetHealthController extends Controller
{
    protected PetHealthService $healthService;

    public function __construct(PetHealthService $healthService)
    {
        $this->healthService = $healthService;
    }

    public function index($petId): JsonResponse
    {
        return response()->json($this->healthService->getHealthRecords($petId));
    }

    public function storeVaccination(Request $request, $petId): JsonResponse
    {
        $data = $request->validate([
            'vaccine_type' => 'required|string|max:150',
            'application_date' => 'required|date',
            'expiry_date' => 'nullable|date',
            'clinic_name' => 'nullable|string|max:255',
            'observations' => 'nullable|string',
        ]);

        $data['pet_id'] = $petId;
        $vaccination = $this->healthService->addVaccination($data);

        return response()->json($vaccination, 201);
    }

    public function storePhoto(Request $request, $petId): JsonResponse
    {
        $request->validate([
            'file' => 'required|image|max:5120', // 5MB
            'type' => 'nullable|string',
            'appointment_id' => 'nullable|integer',
        ]);

        $photo = $this->healthService->uploadPhoto(
            $petId,
            $request->file('file'),
            $request->input('type', 'general'),
            $request->input('appointment_id')
        );

        return response()->json($photo, 201);
    }
}
