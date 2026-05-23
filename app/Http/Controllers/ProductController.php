<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\Stock;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    public function index(): JsonResponse
    {
        $products = $this->inventoryService->getProducts()
            ->map(fn (Product $product) => $this->toArray($product));

        return response()->json($products);
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $product = $this->inventoryService->saveProduct($request->validated());

        return response()->json($this->toArray($product), 201);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['category', 'brand', 'unit', 'inventoryStock']);

        return response()->json($this->toArray($product));
    }

    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();
        $minStock = $data['min_stock'] ?? $data['stock_min'] ?? $product->min_stock;

        $product->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? $product->description,
            'price' => $data['price'],
            'stock' => $data['stock'],
            'min_stock' => $minStock,
            'stock_min' => $minStock,
            'stock_max' => $data['stock_max'] ?? $product->stock_max,
            'sku' => $data['sku'] ?? $product->sku,
            'category_id' => $data['category_id'],
            'brand_id' => $data['brand_id'],
            'unit_id' => $data['unit_id'],
            'is_basic_supply' => $data['is_basic_supply'] ?? $product->is_basic_supply,
            'is_active' => $data['is_active'] ?? $product->is_active,
        ]);

        $inv = $product->inventoryStock;
        if ($inv) {
            $inv->update([
                'current_quantity' => $product->stock,
                'last_update' => now(),
            ]);
        } else {
            Stock::create([
                'product_id' => $product->id,
                'current_quantity' => $product->stock,
            ]);
        }

        $product->load(['category', 'brand', 'unit', 'inventoryStock']);

        return response()->json($this->toArray($product));
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => (float) $product->price,
            'stock' => $product->current_stock,
            'min_stock' => $product->minimum_stock_threshold,
            'sku' => $product->sku,
            'code' => $product->code,
            'category_id' => $product->category_id,
            'brand_id' => $product->brand_id,
            'unit_id' => $product->unit_id,
            'is_basic_supply' => (bool) $product->is_basic_supply,
            'is_active' => (bool) $product->is_active,
            'low_stock' => $product->isBelowMinStock(),
            'category' => $product->category?->name,
            'brand' => $product->brand?->name,
            'unit' => $product->unit?->name,
        ];
    }
}
