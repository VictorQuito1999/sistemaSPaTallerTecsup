<?php

return [

    /*
    | SKU del insumo básico por defecto (ej. champú base) si no hay consumibles
    | definidos en el servicio ni productos marcados como is_basic_supply.
    */
    'default_basic_sku' => env('INVENTORY_DEFAULT_BASIC_SKU', 'SHP-BASE'),

    /** Cantidad por defecto a descontar al finalizar una cita. */
    'default_deduct_quantity' => 1,

];
