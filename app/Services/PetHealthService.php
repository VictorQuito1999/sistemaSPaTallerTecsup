<?php

namespace App\Services;

use App\Models\PetPhoto;
use App\Models\PetVaccination;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PetHealthService
{
    public function addVaccination(array $data)
    {
        return PetVaccination::create($data);
    }

    public function uploadPhoto(int $petId, UploadedFile $file, string $type = 'general', ?int $appointmentId = null)
    {
        $path = $file->store("pets/{$petId}/photos", 'public');

        return PetPhoto::create([
            'pet_id' => $petId,
            'appointment_id' => $appointmentId,
            'type' => $type,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
        ]);
    }

    public function getHealthRecords(int $petId)
    {
        return [
            'vaccinations' => PetVaccination::where('pet_id', $petId)->orderBy('application_date', 'desc')->get(),
            'photos' => PetPhoto::where('pet_id', $petId)->orderBy('created_at', 'desc')->get(),
        ];
    }
}
