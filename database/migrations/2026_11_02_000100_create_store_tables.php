<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Todos los montos se guardan como enteros en centavos para evitar errores
 * de redondeo. Se formatean para mostrar con el helper money().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('cost')->default(0);
            $table->string('image_path')->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->unsignedSmallInteger('low_stock_at')->default(3);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('size', 20);
            $table->unsignedInteger('stock')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'size']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable()->unique();
            $table->string('public_token', 40)->unique();
            $table->string('channel', 20)->default('web');
            $table->string('customer_name');
            $table->string('customer_phone', 40)->nullable();
            $table->string('delivery_method', 20)->default('retiro');
            $table->string('address')->nullable();
            $table->string('payment_preference', 30)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('total')->default(0);
            $table->unsignedBigInteger('amount_paid')->default(0);
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('size', 20);
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('unit_cost')->default(0);
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('line_total');
            $table->timestamps();
        });

        // amount es negativo cuando se trata de una devolución.
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('amount');
            $table->string('method', 30);
            $table->string('note')->nullable();
            $table->timestamp('paid_at');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index('paid_at');
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->integer('quantity');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('concept');
            $table->unsignedBigInteger('amount');
            $table->timestamp('moved_at');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index('moved_at');
        });

        // Foto fija del día al momento del cierre: no cambia aunque después
        // se modifiquen pedidos antiguos.
        Schema::create('cash_closings', function (Blueprint $table) {
            $table->id();
            $table->string('day', 10)->unique();
            $table->bigInteger('opening_amount')->default(0);
            $table->json('payments_by_method');
            $table->bigInteger('collected_total')->default(0);
            $table->bigInteger('cash_in')->default(0);
            $table->bigInteger('cash_out')->default(0);
            $table->bigInteger('expected_cash')->default(0);
            $table->bigInteger('counted_cash')->default(0);
            $table->bigInteger('difference')->default(0);
            $table->unsignedInteger('customers_count')->default(0);
            $table->unsignedInteger('orders_count')->default(0);
            $table->unsignedInteger('orders_paid_count')->default(0);
            $table->unsignedInteger('orders_pending_count')->default(0);
            $table->unsignedInteger('items_count')->default(0);
            $table->bigInteger('sales_total')->default(0);
            $table->bigInteger('gross_profit')->default(0);
            $table->bigInteger('pending_balance')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_closings');
        Schema::dropIfExists('cash_movements');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
