<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentConsumable;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function processFinishedAppointment(Appointment $appointment): array
    {
        $appointment->loadMissing(['service.consumables', 'consumables.product']);

        if ($appointment->consumables->isNotEmpty()) {
            return $this->collectLowStockAlerts(
                $appointment->consumables->map(fn (AppointmentConsumable $c) => $c->product)->filter(),
            );
        }

        $products = $this->resolveProductsToDeduct($appointment);
        $alerts = [];

        DB::transaction(function () use ($appointment, $products, &$alerts) {
            foreach ($products as $product) {
                $qty = (float) ($product->pivot->estimated_quantity ?? config('inventory.default_deduct_quantity', 1));

                $this->decrementStock($product, $qty);

                AppointmentConsumable::create([
                    'appointment_id' => $appointment->id,
                    'product_id' => $product->id,
                    'quantity_used' => $qty,
                ]);

                $product->refresh();
                if ($product->isBelowMinStock()) {
                    $alerts[] = $this->buildLowStockAlert($product);
                }
            }
        });

        if ($alerts !== []) {
            Log::warning('Inventario: stock por debajo del mínimo tras finalizar cita', [
                'appointment_id' => $appointment->id,
                'alerts' => $alerts,
            ]);
        }

        return $alerts;
    }

    public function decrementStock(Product $product, float $quantity = 1): void
    {
        $quantity = max(0, $quantity);
        $newStock = max(0, (float) $product->current_stock - $quantity);

        $product->stock = $newStock;
        if ($product->stock_min === null && $product->min_stock !== null) {
            $product->stock_min = $product->min_stock;
        }
        $product->save();

        $invStock = $product->inventoryStock()->first();
        if ($invStock) {
            $invStock->current_quantity = $newStock;
            $invStock->last_update = now();
            $invStock->save();
        }
    }

    public function decrementStockByProductId(int $productId, float $quantity = 1): void
    {
        $product = Product::findOrFail($productId);
        $this->decrementStock($product, $quantity);
    }

    /**
     * @return Collection<int, Product>
     */
    protected function resolveProductsToDeduct(Appointment $appointment): Collection
    {
        $serviceProducts = $appointment->service?->consumables ?? collect();
        if ($serviceProducts->isNotEmpty()) {
            return $serviceProducts;
        }

        $basic = Product::query()
            ->where('is_active', true)
            ->where('is_basic_supply', true)
            ->get();

        if ($basic->isNotEmpty()) {
            return $basic;
        }

        $sku = config('inventory.default_basic_sku', 'SHP-BASE');
        $fallback = Product::query()
            ->where('is_active', true)
            ->where('sku', $sku)
            ->get();

        return $fallback;
    }

    /**
     * @param  Collection<int, Product|null>  $products
     * @return array<int, array<string, mixed>>
     */
    protected function collectLowStockAlerts(Collection $products): array
    {
        $alerts = [];
        foreach ($products as $product) {
            if ($product && $product->isBelowMinStock()) {
                $alerts[] = $this->buildLowStockAlert($product);
            }
        }

        if ($alerts !== []) {
            Log::warning('Inventario: alerta de stock bajo (cita finalizada)', ['alerts' => $alerts]);
        }

        return $alerts;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildLowStockAlert(Product $product): array
    {
        return [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'stock' => $product->current_stock,
            'min_stock' => $product->minimum_stock_threshold,
            'message' => "Stock bajo: {$product->name} ({$product->current_stock} ≤ mínimo {$product->minimum_stock_threshold})",
        ];
    }

    public function getProducts()
    {
        return Product::with(['category', 'brand', 'unit', 'inventoryStock'])->orderBy('name')->get();
    }

    public function getProductsByCategory($category_id)
    {
        return Product::whereCategoryId($category_id)->with('inventoryStock')->paginate(20);
    }

    public function getProductsByBrand($brand_id)
    {
        return Product::whereBrandId($brand_id)->with('inventoryStock')->paginate(20);
    }

    public function saveProduct(array $data): Product
    {
        try {
            DB::beginTransaction();

            $minStock = $data['min_stock'] ?? $data['stock_min'] ?? 0;

            $product = Product::create([
                'name' => $data['name'],
                'category_id' => $data['category_id'],
                'brand_id' => $data['brand_id'],
                'unit_id' => $data['unit_id'],
                'sku' => $data['sku'] ?? null,
                'description' => $data['description'] ?? '',
                'price' => $data['price'] ?? 0,
                'stock' => $data['stock'] ?? 0,
                'min_stock' => $minStock,
                'stock_min' => $minStock,
                'stock_max' => $data['stock_max'] ?? ($data['stock'] ?? 0) * 2,
                'is_basic_supply' => $data['is_basic_supply'] ?? false,
                'is_active' => $data['is_active'] ?? true,
            ]);

            Stock::create([
                'product_id' => $product->id,
                'current_quantity' => $product->stock,
            ]);

            DB::commit();

            return $product->fresh(['category', 'brand', 'unit', 'inventoryStock']);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al guardar producto: '.$e->getMessage());
            throw $e;
        }
    }
}
