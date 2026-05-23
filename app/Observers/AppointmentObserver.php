<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Services\InventoryService;

class AppointmentObserver
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    public function updated(Appointment $appointment): void
    {
        if (! $appointment->wasChanged('status')) {
            return;
        }

        $newStatus = $appointment->status;
        $oldStatus = $appointment->getOriginal('status');

        if (! in_array($newStatus, ['finished', 'paid'])) {
            return;
        }

        // Avoid running again if it was already finished or paid
        if (in_array($oldStatus, ['finished', 'paid'])) {
            return;
        }

        $appointment->inventory_alerts = $this->inventoryService->processFinishedAppointment(
            $appointment->fresh(['service.consumables', 'consumables.product'])
        );
    }
}
