<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppointmentRequest;
use App\Models\Appointment;
use App\Services\AppointmentService;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;


class AppointmentController extends Controller
{
    protected AppointmentService $appointmentService;

    public function __construct(
        AppointmentService $appService,
        protected InventoryService $inventoryService,
    ) {
        $this->appointmentService = $appService;
    }

    public function index(Request $request): JsonResponse
    {
        // If a month/year is specified or frontend wants all data, AppointmentService currently returns ALL.
        // We'll leave it as is for now, but we can filter by month if needed.
        $appointments = $this->appointmentService->getAppointment()
            ->map(fn (Appointment $a) => $this->appointmentService->formatForCalendar($a));

        return response()->json($appointments);
    }

    public function pendingCount(): JsonResponse
    {
        $count = Appointment::where('status', 'pending')->count();
        return response()->json(['count' => $count]);
    }

    public function pendingList(): JsonResponse
    {
        $appointments = Appointment::with(['pet.breed', 'service', 'pet.customer'])
            ->where('status', 'pending')
            ->orderBy('appointment_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get()
            ->map(fn (Appointment $a) => $this->appointmentService->formatForCalendar($a));

        return response()->json($appointments);
    }

    /**
     * Devuelve las citas asignadas al empleado autenticado.
     */
    public function myAppointments(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([], 403);
        }

        // Rango ampliado para el calendario mensual (mes anterior, actual y siguiente)
        $start = now()->subMonth()->startOfMonth();
        $end = now()->addMonths(2)->endOfMonth();

        $appointments = Appointment::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('appointment_date', [$start->toDateString(), $end->toDateString()])
            ->with(['pet.breed', 'service', 'pet.customer'])
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->get()
            ->map(fn (Appointment $a) => $this->appointmentService->formatForCalendar($a));

        return response()->json($appointments);
    }

    public function estimate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'service_id' => 'required|exists:services,id',
            'pet_id' => 'required|exists:pets,id',
        ]);

        $estimation = $this->appointmentService->estimateAppointment($data['service_id'], $data['pet_id']);

        return response()->json($estimation);
    }


    public function store(AppointmentRequest $request): JsonResponse
    {
        try {
            $appointment = $this->appointmentService->createFromValidated($request->validated());
            $appointment->load(['pet', 'service', 'employee']);

            return response()->json(
                $this->appointmentService->formatForCalendar($appointment),
                201,
            );
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'start_time' => [$e->getMessage()],
            ]);
        }
    }

    public function show(Appointment $appointment)
    {
        //
    }

    public function update(Request $request, Appointment $appointment): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|string|in:pending,confirmed,in_progress,finished,cancelled,paid',
            'employee_id' => 'nullable|exists:employees,id',
            'start_time' => 'nullable|string',
            'payment_type' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if (in_array($data['status'], ['finished', 'paid']) && empty($appointment->payment_type) && empty($data['payment_type'])) {
            throw ValidationException::withMessages([
                'payment_type' => ['No se puede finalizar la cita sin asignar un método de pago.'],
            ]);
        }

        // If employee or time changes, recalculate times using AppointmentService rules
        if (!empty($data['employee_id']) || !empty($data['start_time'])) {
            $newStartTime = $data['start_time'] ?? ($appointment->appointment_date . ' ' . $appointment->start_time);
            $newEmployeeId = $data['employee_id'] ?? $appointment->employee_id;

            $calculated = $this->appointmentService->estimateAppointment($appointment->service_id, $appointment->pet_id);
            $duration = $calculated['duration']['total'];
            
            $parsedStart = new \DateTime($newStartTime);
            $parsedEnd = clone $parsedStart;
            $parsedEnd->modify("+{$duration} minutes");

            $dayOfWeek = (int)$parsedStart->format('N');
            $startHour = (int)$parsedStart->format('H');
            $endHour = (int)$parsedEnd->format('H');
            $endMin = (int)$parsedEnd->format('i');
            
            if ($dayOfWeek == 7) {
                throw ValidationException::withMessages([
                    'start_time' => ['El Spa está cerrado los Domingos.'],
                ]);
            }

            if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                if ($startHour < 9 || ($endHour > 18 || ($endHour === 18 && $endMin > 0))) {
                    throw ValidationException::withMessages([
                        'start_time' => ['La cita debe estar dentro del horario laboral de Lunes a Viernes de 09:00 a 18:00.'],
                    ]);
                }
            }

            if ($dayOfWeek == 6) {
                if ($startHour < 9 || ($endHour > 12 || ($endHour === 12 && $endMin > 0))) {
                    throw ValidationException::withMessages([
                        'start_time' => ['La cita debe estar dentro del horario laboral de Sábados de 09:00 a 12:00.'],
                    ]);
                }
            }

            $appointment->appointment_date = $parsedStart->format('Y-m-d');
            $appointment->start_time = $parsedStart->format('H:i:s');
            $appointment->end_time = $parsedEnd->format('H:i:s');
            $appointment->employee_id = $newEmployeeId;
        }

        $originalStatus = $appointment->status;
        
        $appointment->status = $data['status'];
        if (isset($data['notes'])) {
            $appointment->notes = $data['notes'];
        }
        if (isset($data['payment_type'])) {
            $appointment->payment_type = $data['payment_type'];
        }

        $appointment->save();

        if ($originalStatus === 'pending' && $appointment->status === 'confirmed') {
            $appointment->load(['pet.customer.user', 'service', 'employee.user']);
            
            $customerEmail = $appointment->pet?->customer?->user?->email;
            if ($customerEmail) {
                try {
                    \Illuminate\Support\Facades\Mail::to($customerEmail)
                        ->send(new \App\Mail\AppointmentApprovedMail($appointment));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error sending appointment approved email: ' . $e->getMessage());
                }
            }
        }

        return response()->json($this->appointmentService->formatForCalendar($appointment));
    }

    public function getGroomingConsole(Appointment $appointment): JsonResponse
    {
        $appointment->load(['pet.breed.category', 'pet.customer', 'service']);

        $service = $appointment->service;
        $checklistTemplate = $service->checklist_template;

        if (empty($checklistTemplate)) {
            $checklistTemplate = [
                ['id' => 'c1', 'label' => 'Revisar piel y orejas', 'completed' => false],
                ['id' => 'c2', 'label' => 'Baño con shampoo seleccionado', 'completed' => false],
                ['id' => 'c3', 'label' => 'Secado y cepillado', 'completed' => false],
                ['id' => 'c4', 'label' => 'Corte higiénico / estilismo', 'completed' => false],
                ['id' => 'c5', 'label' => 'Foto final y nota en sistema', 'completed' => false],
            ];
        }

        // Obtener productos disponibles en inventario
        $products = \App\Models\Product::where('is_active', true)
            ->with(['unit'])
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'stock' => $product->stock,
                    'unit' => $product->unit?->name ?? 'unidad',
                ];
            });

        return response()->json([
            'appointment_id' => $appointment->id,
            'pet_name' => $appointment->pet?->name,
            'pet_weight' => $appointment->pet?->weight_kg,
            'pet_breed' => $appointment->pet?->breed?->name,
            'customer_name' => $appointment->pet?->customer?->full_name,
            'service_name' => $service->name,
            'estimated_minutes' => $this->appointmentService->calculateDuration($service->id, $appointment->pet_id),
            'checklist' => $checklistTemplate,
            'supplies_catalog' => $products,
            'notes' => $appointment->notes,
        ]);
    }

    public function finishGrooming(Request $request, Appointment $appointment): JsonResponse
    {
        $data = $request->validate([
            'checklist' => 'required|array',
            'actual_duration_min' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'supplies' => 'nullable|array',
            'supplies.*' => 'exists:products,id',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($appointment, $data) {
            // Guardar detalles
            $appointment->groomingDetail()->create([
                'checklist' => $data['checklist'],
                'actual_duration_min' => $data['actual_duration_min'],
                'notes' => $data['notes'] ?? null,
            ]);

            if (! empty($data['supplies'])) {
                foreach ($data['supplies'] as $productId) {
                    $appointment->consumables()->create([
                        'product_id' => $productId,
                        'quantity_used' => 1.00,
                    ]);

                    $this->inventoryService->decrementStockByProductId((int) $productId, 1.00);
                }
            }

            $appointment->status = 'finished';
            $appointment->save();
        });

        $appointment->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Servicio finalizado e inventario descontado con éxito.',
            'inventory_alerts' => $appointment->inventory_alerts ?? [],
        ]);
    }

    public function destroy(Appointment $appointment)
    {
        $appointment->delete();
        return response()->json(null, 204);
    }
}
