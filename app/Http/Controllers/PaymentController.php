<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Appointment;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Store a newly created payment resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'appointment_id' => 'nullable|exists:appointments,id',
            'total_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'items' => 'nullable|array',
            'items.*.productId' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $inventoryService = app(InventoryService::class);
        $lowStockAlerts = [];

        $payment = DB::transaction(function () use ($data, $inventoryService, &$lowStockAlerts) {
            $payment = Payment::create([
                'appointment_id' => $data['appointment_id'],
                'total_amount' => $data['total_amount'],
                'payment_method' => $data['payment_method'],
            ]);

            // 1. Process Appointment if present
            if ($data['appointment_id']) {
                $appointment = Appointment::findOrFail($data['appointment_id']);
                $appointment->status = 'paid';
                $appointment->payment_type = $data['payment_method'];
                $appointment->save();

                if (isset($appointment->inventory_alerts) && is_array($appointment->inventory_alerts)) {
                    $lowStockAlerts = array_merge($lowStockAlerts, $appointment->inventory_alerts);
                }
            }

            // 2. Process Retail Items Stock Deduction
            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $product = Product::findOrFail($item['productId']);
                    $qty = (float) $item['quantity'];

                    $inventoryService->decrementStock($product, $qty);

                    $product->refresh();
                    if ($product->isBelowMinStock()) {
                        $lowStockAlerts[] = $inventoryService->buildLowStockAlert($product);
                    }
                }
            }

            return $payment;
        });

        return response()->json([
            'success' => true,
            'message' => 'Pago registrado correctamente.',
            'payment' => $payment,
            'low_stock_alerts' => $lowStockAlerts,
        ], 201);
    }
}
