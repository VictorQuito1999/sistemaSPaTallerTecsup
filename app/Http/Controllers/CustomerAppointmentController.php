<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Pet;
use App\Services\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CustomerAppointmentController extends Controller
{
    public function __construct(
        protected AppointmentService $appointmentService
    ) {}

    public function requestAppointment(Request $request): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer;

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes un perfil de cliente registrado.'
            ], 403);
        }

        $data = $request->validate([
            'pet_id' => 'required|exists:pets,id',
            'service_id' => 'required|exists:services,id',
            'start_time' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        // Verify the pet belongs to the authenticated customer
        $pet = Pet::where('id', $data['pet_id'])
            ->where('customer_id', $customer->id)
            ->first();

        if (!$pet) {
            return response()->json([
                'success' => false,
                'message' => 'La mascota seleccionada no pertenece a tu perfil.'
            ], 403);
        }

        // Fetch a default active groomer as a database placeholder (required by DB constraints)
        // The administrator can reassign this employee when they approve the pending request.
        $defaultGroomer = \App\Models\Employee::where('specialty', 'Groomer')
            ->whereHas('user', function ($q) {
                $q->where('is_active', true);
            })
            ->first() 
            ?? \App\Models\Employee::whereHas('user', function ($q) {
                $q->where('is_active', true);
            })->first() 
            ?? \App\Models\Employee::first();

        if (!$defaultGroomer) {
            return response()->json([
                'success' => false,
                'message' => 'No hay empleados disponibles en el sistema para procesar la solicitud.'
            ], 422);
        }

        try {
            // Prepare inputs for the central AppointmentService
            $appointmentData = [
                'pet_id' => $data['pet_id'],
                'service_id' => $data['service_id'],
                'start_time' => $data['start_time'],
                'notes' => $data['notes'] ?? null,
                'employee_id' => $defaultGroomer->id,
                'status' => 'pending',
            ];

            $appointment = $this->appointmentService->createFromValidated($appointmentData);
            $appointment->load(['pet', 'service']);

            return response()->json([
                'success' => true,
                'message' => 'Su solicitud ha sido enviada con éxito y está en espera de aprobación por parte del Spa.',
                'appointment' => $this->appointmentService->formatForCalendar($appointment)
            ], 201);

        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'start_time' => [$e->getMessage()]
            ]);
        }
    }

    public function getCustomerAppointments(Request $request): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer;

        if (!$customer) {
            return response()->json([], 403);
        }

        $appointments = Appointment::whereHas('pet', function ($query) use ($customer) {
            $query->where('customer_id', $customer->id);
        })
        ->with(['pet.breed', 'service'])
        ->orderBy('appointment_date', 'desc')
        ->orderBy('start_time', 'desc')
        ->get()
        ->map(fn (Appointment $a) => $this->appointmentService->formatForCalendar($a));

        return response()->json($appointments);
    }
}
