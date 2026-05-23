<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'category_id',
        'brand_id',
        'unit_id',
        'sku',
        'description',
        'price',
        'stock',
        'min_stock',
        'stock_min',
        'stock_max',
        'is_basic_supply',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'decimal:2',
        'min_stock' => 'decimal:2',
        'stock_min' => 'decimal:2',
        'stock_max' => 'decimal:2',
        'is_basic_supply' => 'boolean',
        'is_active' => 'boolean',
    ];

    /** Alertas generadas al pasar la cita a finished (no persistido). */
    public ?array $inventory_alerts = null;

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /** Registro en inv_stocks (evita conflicto con columna `stock`). */
    public function inventoryStock()
    {
        return $this->hasOne(Stock::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'service_consumables')
            ->withPivot('estimated_quantity')
            ->withTimestamps();
    }

    /** Código interno (alias de SKU). */
    public function getCodeAttribute(): ?string
    {
        return $this->sku;
    }

    public function getCurrentStockAttribute(): float
    {
        if (isset($this->attributes['stock'])) {
            return (float) $this->attributes['stock'];
        }

        return (float) ($this->inventoryStock?->current_quantity ?? 0);
    }

    public function getMinimumStockThresholdAttribute(): float
    {
        if (isset($this->attributes['min_stock'])) {
            return (float) $this->attributes['min_stock'];
        }

        return (float) ($this->attributes['stock_min'] ?? 0);
    }

    public function isBelowMinStock(): bool
    {
        return $this->current_stock <= $this->minimum_stock_threshold;
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereColumn('stock', '<=', 'min_stock')
                ->orWhere(function (Builder $q2) {
                    $q2->whereNull('min_stock')
                        ->whereColumn('stock', '<=', 'stock_min');
                });
        });
    }
}
