<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')->update(['price' => DB::raw('ROUND(price * 100)')]);
        DB::table('orders')->update([
            'subtotal'        => DB::raw('ROUND(subtotal * 100)'),
            'discount_amount' => DB::raw('ROUND(discount_amount * 100)'),
            'total'           => DB::raw('ROUND(total * 100)'),
        ]);
        DB::table('order_items')->update(['unit_price' => DB::raw('ROUND(unit_price * 100)')]);

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('price')->change();
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('subtotal')->change();
            $table->unsignedInteger('discount_amount')->default(0)->change();
            $table->unsignedInteger('total')->change();
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedInteger('unit_price')->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->change();
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal', 10, 2)->change();
            $table->decimal('discount_amount', 10, 2)->default(0)->change();
            $table->decimal('total', 10, 2)->change();
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('unit_price', 10, 2)->change();
        });

        DB::table('products')->update(['price' => DB::raw('price / 100.0')]);
        DB::table('orders')->update([
            'subtotal'        => DB::raw('subtotal / 100.0'),
            'discount_amount' => DB::raw('discount_amount / 100.0'),
            'total'           => DB::raw('total / 100.0'),
        ]);
        DB::table('order_items')->update(['unit_price' => DB::raw('unit_price / 100.0')]);
    }
};
