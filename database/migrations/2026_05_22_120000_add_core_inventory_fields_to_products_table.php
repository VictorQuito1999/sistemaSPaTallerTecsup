<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'price')) {
                $table->decimal('price', 10, 2)->default(0)->after('description');
            }
            if (! Schema::hasColumn('products', 'stock')) {
                $table->decimal('stock', 12, 2)->default(0)->after('price');
            }
            if (! Schema::hasColumn('products', 'min_stock')) {
                $table->decimal('min_stock', 10, 2)->default(0)->after('stock');
            }
            if (! Schema::hasColumn('products', 'is_basic_supply')) {
                $table->boolean('is_basic_supply')->default(false)->after('min_stock');
            }
        });

        if (Schema::hasColumn('products', 'stock_min')) {
            DB::table('products')
                ->whereNull('min_stock')
                ->update(['min_stock' => DB::raw('stock_min')]);
        }

        if (Schema::hasTable('inv_stocks') && DB::getDriverName() === 'mysql') {
            DB::statement('
                UPDATE products p
                INNER JOIN inv_stocks s ON s.product_id = p.id
                SET p.stock = s.current_quantity
                WHERE p.stock = 0 OR p.stock IS NULL
            ');
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columns = ['price', 'stock', 'min_stock', 'is_basic_supply'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
