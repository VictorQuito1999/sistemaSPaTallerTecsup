<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Pet;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class AppointmentService
{
    public function getAppointment()
    {
        return Appointment::with(['pet', 'service', 'employee.user'])->orderBy('appointment_date')->orderBy('start_time')->get();
    }

    public function estimateAppointment($serviceId, $petId): array
    {
        $service = Service::findOrFail($serviceId);
        $pet = Pet::findOrFail($petId);

        // --- PRECIO ---
        $basePrice = (float) $service->base_price;
        $breedFactor = (float) ($pet->breed->duration_factor ?? 1.0);
        $totalPrice = round($basePrice * $breedFactor, 2);
        $priceSurcharge = round($totalPrice - $basePrice, 2);

        // --- DURACIÓN ---
        $baseDuration = (int) $service->base_duration_min;
        $weight = (float) $pet->weight_kg;
        
        $durationWithBreed = $baseDuration * $breedFactor;
        
        $finalDuration = $durationWithBreed;
        if ($weight > 25) {
            $finalDuration *= 1.30;
        } elseif ($weight >= 10) {
            $finalDuration *= 1.15;
        }
        
        $finalDuration = (int) round($finalDuration);
        $durationSurcharge = $finalDuration - $baseDuration;

        return [
            'price' => [
                'base' => $basePrice,
                'surcharge' => $priceSurcharge,
                'total' => $totalPrice,
            ],
            'duration' => [
                'base' => $baseDuration,
                'surcharge' => $durationSurcharge,
                'total' => $finalDuration,
            ],
            'pet_name' => $pet->name,
            'breed_name' => $pet->breed->name ?? 'N/A',
            'weight_kg' => $weight,
        ];
    }

    public function calculateAgreedPrice($serviceId, $petId)
    {
        $est = $this->estimateAppointment($serviceId, $petId);
        return $est['price']['total'];
    }


    public function calculateDuration($service_id, $petId)
    {
        $est = $this->estimateAppointment($service_id, $petId);
        return $est['duration']['total'];
    }



    /**
     * Crea cita a partir de datos validados por AppointmentRequest.
     *
     * @throws InvalidArgumentException cuando hay choque de horario
     */
    public function createFromValidated(array $data): Appointment
    {
        $duration = isset($data['duration']) && $data['duration']
            ? (int) $data['duration']
            : $this->calculateDuration($data['service_id'], $data['pet_id']);

        $start = Carbon::parse($data['start_time']);
        $end = $start->copy()->addMinutes($duration);

        // 1. Validar Horario General del Spa
        $dayOfWeek = $start->format('N'); // 1 = Monday, 7 = Sunday
        $startHour = (int) $start->format('H');
        $endHour = (int) $end->format('H');
        $endMin = (int) $end->format('i');

        if ($dayOfWeek == 7) {
            throw new InvalidArgumentException('El Spa está cerrado los Domingos.');
        }

        // Lunes a Viernes: 09:00 a 18:00
        if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
            if ($startHour < 9 || ($endHour > 18 || ($endHour === 18 && $endMin > 0))) {
                throw new InvalidArgumentException('El horario laboral de Lunes a Viernes es de 09:00 a 18:00.');
            }
        }

        // Sábados: 09:00 a 12:00
        if ($dayOfWeek == 6) {
            if ($startHour < 9 || ($endHour > 12 || ($endHour === 12 && $endMin > 0))) {
                throw new InvalidArgumentException('El horario laboral de los Sábados es de 09:00 a 12:00.');
            }
        }

        // 3. Validar Bloqueo de Horarios (Mantenimiento, Feriados, Ausencia)
        $startStr = $start->format('Y-m-d H:i:s');
        $endStr = $end->format('Y-m-d H:i:s');

        $blockQuery = \App\Models\TimeBlock::where(function ($query) use ($startStr, $endStr) {
            $query->where('start_time', '<', $endStr)
                  ->where('end_time', '>', $startStr);
        })->where(function ($query) use ($data) {
            $query->where('is_global', true);
            if (!empty($data['employee_id'])) {
                $query->orWhere('employee_id', $data['employee_id']);
            }
        });

        if ($blockQuery->exists()) {
            $block = $blockQuery->first();
            $msg = $block->is_global 
                ? "El Spa está cerrado en ese horario por: {$block->reason}."
                : "El groomer seleccionado no está disponible en ese horario por: {$block->reason}.";
            throw new InvalidArgumentException($msg);
        }

        if (! empty($data['employee_id'])) {
            $employee = \App\Models\Employee::find($data['employee_id']);
            
            if (!$employee || $employee->specialty !== 'Groomer') {
                throw new InvalidArgumentException('Solo se pueden asignar citas a empleados con la especialidad Groomer.');
            }

            // 2. Validar Turno del Empleado (mañana / tarde)
            $shift = strtolower($employee->shift ?? '');
            if ($shift === 'mañana') {
                $shiftEnd = Carbon::parse($start->format('Y-m-d') . ' 14:00:00');
                if ($end->gt($shiftEnd)) {
                    throw new InvalidArgumentException('El groomer seleccionado no trabaja en ese turno.');
                }
            } elseif ($shift === 'tarde') {
                $shiftStart = Carbon::parse($start->format('Y-m-d') . ' 14:00:00');
                if ($start->lt($shiftStart)) {
                    throw new InvalidArgumentException('El groomer seleccionado no trabaja en ese turno.');
                }
            }

            if (! $this->checkAvailability($data['employee_id'], $data['start_time'], $duration)) {
                throw new InvalidArgumentException('El groomer ya tiene una cita en ese horario. Elige otro horario o empleado.');
            }
        }

        return $this->saveAppointment([
            'pet_id' => $data['pet_id'],
            'employee_id' => $data['employee_id'] ?? null,
            'service_id' => $data['service_id'],
            'appointment_date' => $start->toDateString(),
            'start_time' => $start->format('H:i:s'),
            'end_time' => $end->format('H:i:s'),
            'status' => $data['status'],
            'agreed_price' => $this->calculateAgreedPrice($data['service_id'], $data['pet_id']),
            'notes' => $data['notes'] ?? null,
            'payment_type' => $data['payment_type'] ?? null,
        ]);
    }

    public function saveAppointment(array $data)
    {
        try {
            DB::beginTransaction();
            $appointment = Appointment::create([
                'pet_id' => $data['pet_id'],
                'employee_id' => $data['employee_id'],
                'service_id' => $data['service_id'],
                'appointment_date' => $data['appointment_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'status' => $data['status'],
                'agreed_price' => $data['agreed_price'],
                'payment_type' => $data['payment_type'] ?? null,
                'notes' => $data['notes'],
            ]);

            DB::commit();

            return $appointment;

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al guardar cita', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function checkAvailability($employeeId, $startTime, $duration)
    {
        $start = Carbon::parse($startTime);
        $end = $start->copy()->addMinutes($duration);

        $conflict = Appointment::where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
            ->where(function ($query) use ($start, $end) {
                $query->whereRaw("CONCAT(appointment_date, ' ', start_time) < ?", [$end->format('Y-m-d H:i:s')])
                    ->whereRaw("CONCAT(appointment_date, ' ', end_time) > ?", [$start->format('Y-m-d H:i:s')]);
            })
            ->exists();

        return ! $conflict;
    }

    /** Serializa start_time / end_time como Y-m-d H:i para el calendario Vue. */
    public function formatForCalendar(Appointment $appointment): array
    {
        $date = $appointment->appointment_date instanceof Carbon
            ? $appointment->appointment_date->format('Y-m-d')
            : Carbon::parse($appointment->appointment_date)->format('Y-m-d');

        $startRaw = $appointment->start_time;
        $endRaw = $appointment->end_time;
        $startTime = strlen((string) $startRaw) <= 8 ? substr((string) $startRaw, 0, 8) : $startRaw;
        $endTime = strlen((string) $endRaw) <= 8 ? substr((string) $endRaw, 0, 8) : $endRaw;

        return [
            'id' => $appointment->id,
            'pet_id' => $appointment->pet_id,
            'service_id' => $appointment->service_id,
            'employee_id' => $appointment->employee_id,
            'pet_name' => $appointment->pet?->name,
            'pet_weight' => $appointment->pet?->weight_kg,
            'pet_breed' => $appointment->pet?->breed?->name,
            'customer_name' => $appointment->pet?->customer?->full_name,
            'service_name' => $appointment->service?->name,
            'appointment_date' => $date,
            'start_time' => "{$date} ".substr($startTime, 0, 5),
            'end_time' => "{$date} ".substr($endTime, 0, 5),
            'status' => $appointment->status,
            'agreed_price' => $appointment->agreed_price,
            'notes' => $appointment->notes,
        ];
    }
}
