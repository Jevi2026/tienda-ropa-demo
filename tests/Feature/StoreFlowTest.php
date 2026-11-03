<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CashClosing;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CashRegister;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StoreFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();

        $category = Category::create(['name' => 'Mujer', 'slug' => 'mujer']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Vestido',
            'slug' => 'vestido',
            'price' => 100000,
            'cost' => 40000,
        ]);

        $this->variant = $product->variants()->create(['size' => 'M', 'stock' => 5]);
    }

    private function order(int $quantity = 1, array $customer = []): Order
    {
        return app(OrderService::class)->create(
            $customer + ['customer_name' => 'Ana', 'customer_phone' => '0000-0001'],
            [['variant_id' => $this->variant->id, 'quantity' => $quantity]],
            'web',
        );
    }

    public function test_el_checkout_crea_el_pedido_y_descuenta_inventario(): void
    {
        $this->post(route('cart.add'), ['variant_id' => $this->variant->id, 'quantity' => 2])->assertRedirect(route('cart'));

        $response = $this->post(route('checkout.store'), [
            'customer_name' => 'Ana Pérez',
            'customer_phone' => '9999-0000',
            'delivery_method' => 'retiro',
            'payment_preference' => 'efectivo',
        ]);

        $order = Order::firstOrFail();

        $response->assertRedirect(route('order.public', $order));
        $this->assertSame(200000, $order->total);
        $this->assertSame('pendiente', $order->status());
        $this->assertSame(3, $this->variant->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', ['order_id' => $order->id, 'type' => 'venta', 'quantity' => -2]);
    }

    public function test_el_precio_lo_decide_el_servidor_y_no_el_formulario(): void
    {
        $this->post(route('cart.add'), ['variant_id' => $this->variant->id, 'price' => 1, 'total' => 1]);

        $this->post(route('checkout.store'), [
            'customer_name' => 'Ana',
            'customer_phone' => '9999-0000',
            'delivery_method' => 'retiro',
            'payment_preference' => 'efectivo',
            'total' => 1,
        ]);

        $this->assertSame(100000, Order::firstOrFail()->total);
    }

    public function test_no_se_puede_vender_mas_de_lo_que_hay(): void
    {
        $this->expectException(ValidationException::class);

        try {
            $this->order(6);
        } finally {
            $this->assertSame(5, $this->variant->fresh()->stock);
            $this->assertSame(0, Order::count());
        }
    }

    public function test_el_envio_exige_direccion(): void
    {
        $this->post(route('cart.add'), ['variant_id' => $this->variant->id]);

        $this->post(route('checkout.store'), [
            'customer_name' => 'Ana',
            'customer_phone' => '9999-0000',
            'delivery_method' => 'envio',
            'payment_preference' => 'efectivo',
        ])->assertSessionHasErrors('address');

        $this->assertSame(0, Order::count());
    }

    public function test_abono_y_pago_final_cambian_el_estado(): void
    {
        $order = $this->order();
        $service = app(OrderService::class);

        $service->registerPayment($order, 40000, 'efectivo');
        $this->assertSame('parcial', $order->fresh()->status());
        $this->assertSame(60000, $order->fresh()->balance());

        $service->registerPayment($order, 60000, 'tarjeta');
        $this->assertSame('pagado', $order->fresh()->status());
    }

    public function test_no_se_puede_cobrar_mas_del_saldo(): void
    {
        $order = $this->order();

        $this->expectException(ValidationException::class);

        app(OrderService::class)->registerPayment($order, 100001, 'efectivo');
    }

    public function test_cancelar_devuelve_inventario_y_reversa_el_dinero(): void
    {
        $order = $this->order(2);
        $service = app(OrderService::class);

        $service->registerPayment($order, 150000, 'efectivo');
        $service->cancel($order);

        $order->refresh();
        $this->assertSame('cancelado', $order->status());
        $this->assertSame(0, $order->balance());
        $this->assertSame(5, $this->variant->fresh()->stock);

        $summary = app(CashRegister::class)->summary(now()->toDateString());
        $this->assertSame(0, $summary['collected_total']);
        $this->assertSame(0, $summary['orders_count']);

        $this->expectException(ValidationException::class);
        $service->cancel($order);
    }

    public function test_el_cierre_de_caja_cuadra_efectivo_y_cuenta_personas(): void
    {
        $service = app(OrderService::class);
        $cash = app(CashRegister::class);

        // La misma persona compra dos veces; dos ventas de mostrador cuentan como dos personas.
        $a = $this->order(1);
        $b = $this->order(1);
        $c = $this->order(1, ['customer_name' => Order::WALK_IN_NAME, 'customer_phone' => null]);
        $this->order(1, ['customer_name' => Order::WALK_IN_NAME, 'customer_phone' => null]);

        $service->registerPayment($a, 100000, 'efectivo');
        $service->registerPayment($b, 100000, 'transferencia');
        $service->registerPayment($c, 50000, 'efectivo');
        $cash->addMovement('gasto', 'Bolsas', 10000, $this->admin->id);
        $cash->addMovement('ingreso', 'Cambio', 5000, $this->admin->id);

        $closing = $cash->close(now()->toDateString(), 20000, 160000, null, $this->admin->id);

        $this->assertSame(3, $closing->customers_count);
        $this->assertSame(4, $closing->orders_count);
        $this->assertSame(2, $closing->orders_paid_count);
        $this->assertSame(2, $closing->orders_pending_count);
        $this->assertSame(250000, $closing->collected_total);
        $this->assertSame(165000, $closing->expected_cash); // 20000 + 150000 + 5000 - 10000
        $this->assertSame(-5000, $closing->difference);
        $this->assertSame(150000, $closing->pending_balance);
        $this->assertSame(240000, $closing->gross_profit);
    }

    public function test_un_pedido_de_ayer_cobrado_hoy_cuenta_en_la_caja_de_hoy(): void
    {
        $cash = app(CashRegister::class);
        Carbon::setTestNow(now()->subDay()->setTime(12, 0));
        $yesterday = now()->toDateString();
        $order = $this->order();
        Carbon::setTestNow(now()->addDay());

        app(OrderService::class)->registerPayment($order, 100000, 'efectivo');

        $this->assertSame(0, $cash->summary($yesterday)['collected_total']);
        $this->assertSame(1, $cash->summary($yesterday)['orders_count']);
        $this->assertSame(100000, $cash->summary(now()->toDateString())['collected_total']);
        $this->assertSame(0, $cash->summary(now()->toDateString())['orders_count']);

        Carbon::setTestNow();
    }

    public function test_con_la_caja_cerrada_no_entran_pagos_hasta_reabrir(): void
    {
        $cash = app(CashRegister::class);
        $service = app(OrderService::class);
        $order = $this->order();
        $closing = $cash->close(now()->toDateString(), 0, 0, null, $this->admin->id);

        try {
            $service->registerPayment($order, 100000, 'efectivo');
            $this->fail('El pago debió rechazarse.');
        } catch (ValidationException) {
            $this->assertSame(0, $order->fresh()->amount_paid);
        }

        try {
            $cash->close(now()->toDateString(), 0, 0, null, $this->admin->id);
            $this->fail('No se debe cerrar dos veces.');
        } catch (ValidationException) {
            $this->assertSame(1, CashClosing::count());
        }

        $cash->reopen($closing);
        $service->registerPayment($order, 100000, 'efectivo');

        $this->assertSame('pagado', $order->fresh()->status());
    }

    public function test_la_apertura_arranca_con_lo_contado_el_cierre_anterior(): void
    {
        $cash = app(CashRegister::class);

        Carbon::setTestNow(now()->subDay());
        $cash->close(now()->toDateString(), 0, 75000, null, $this->admin->id);
        Carbon::setTestNow();

        $this->assertSame(75000, $cash->defaultOpening(now()->toDateString()));
    }

    public function test_el_panel_exige_iniciar_sesion(): void
    {
        foreach (['admin.dashboard', 'admin.products.index', 'admin.orders.index', 'admin.cash.index'] as $route) {
            $this->get(route($route))->assertRedirect(route('admin.login'));
        }

        $order = $this->order();
        $this->post(route('admin.orders.pay', $order), ['amount' => 1, 'method' => 'efectivo'])->assertRedirect(route('admin.login'));
        $this->assertSame(0, $order->fresh()->amount_paid);
    }

    public function test_el_panel_muestra_todas_sus_pantallas(): void
    {
        $order = $this->order();
        $this->actingAs($this->admin);

        $this->get(route('admin.dashboard'))->assertOk()->assertSee($order->number);
        $this->get(route('admin.products.index'))->assertOk()->assertSee('Vestido');
        $this->get(route('admin.products.create'))->assertOk();
        $this->get(route('admin.products.edit', $this->variant->product))->assertOk();
        $this->get(route('admin.orders.index', ['estado' => 'pendiente']))->assertOk()->assertSee($order->number);
        $this->get(route('admin.orders.index', ['estado' => 'pagado']))->assertOk()->assertDontSee($order->number);
        $this->get(route('admin.orders.create'))->assertOk();
        $this->get(route('admin.orders.show', $order))->assertOk();
        $this->get(route('admin.cash.index'))->assertOk();
    }

    public function test_guardar_ropa_nueva_con_foto_y_tallas(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin);

        $this->post(route('admin.products.store'), [
            'name' => 'Falda plisada',
            'new_category' => 'Faldas',
            'price' => '850.50',
            'cost' => '300',
            'low_stock_at' => 2,
            'is_active' => 1,
            'image' => UploadedFile::fake()->create('falda.jpg', 200, 'image/jpeg'),
            'variants' => [
                ['size' => 'S', 'stock' => 4],
                ['size' => 'M', 'stock' => 1],
            ],
        ])->assertSessionHasNoErrors();

        $product = Product::where('slug', 'falda-plisada')->firstOrFail();

        $this->assertSame(85050, $product->price);
        $this->assertSame('Faldas', $product->category->name);
        $this->assertSame(5, $product->load('variants')->stockTotal());
        Storage::disk('public')->assertExists($product->image_path);
        $this->assertTrue(Product::active()->lowStock()->whereKey($product->id)->exists());
        $this->assertDatabaseHas('stock_movements', ['product_variant_id' => $product->variants[0]->id, 'type' => 'entrada', 'quantity' => 4]);
    }

    public function test_un_archivo_que_no_es_imagen_se_rechaza(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin);

        $this->post(route('admin.products.store'), [
            'name' => 'Falda',
            'new_category' => 'Faldas',
            'price' => '100',
            'low_stock_at' => 2,
            'image' => UploadedFile::fake()->create('virus.php', 10, 'text/x-php'),
            'variants' => [['size' => 'S', 'stock' => 1]],
        ])->assertSessionHasErrors('image');

        $this->assertSame(0, Product::where('name', 'Falda')->count());
    }

    public function test_editar_inventario_no_pisa_una_venta_hecha_mientras_tanto(): void
    {
        $this->actingAs($this->admin);
        $product = $this->variant->product;

        // El formulario se abrió con 5, entró una venta (quedan 4) y se guardó con 8: se suman 3.
        $this->order(1);

        $this->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'category_id' => $product->category_id,
            'price' => '1000',
            'low_stock_at' => 2,
            'is_active' => 1,
            'variants' => [['id' => $this->variant->id, 'size' => 'M', 'stock' => 8, 'original_stock' => 5]],
        ])->assertSessionHasNoErrors();

        $this->assertSame(7, $this->variant->fresh()->stock);
    }

    public function test_venta_de_mostrador_con_cobro_parcial(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('admin.orders.store'), [
            'delivery_method' => 'retiro',
            'items' => [['variant_id' => $this->variant->id, 'quantity' => 2]],
            'payment_amount' => '500',
            'payment_method' => 'efectivo',
        ])->assertSessionHasNoErrors();

        $order = Order::firstOrFail();

        $this->assertSame(Order::WALK_IN_NAME, $order->customer_name);
        $this->assertSame('tienda', $order->channel);
        $this->assertSame('parcial', $order->status());
        $this->assertSame(150000, $order->balance());
    }

    public function test_si_el_cobro_falla_la_venta_de_mostrador_no_se_crea(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('admin.orders.store'), [
            'delivery_method' => 'retiro',
            'items' => [['variant_id' => $this->variant->id, 'quantity' => 1]],
            'payment_amount' => '5000',
            'payment_method' => 'efectivo',
        ])->assertSessionHasErrors('amount');

        $this->assertSame(0, Order::count());
        $this->assertSame(5, $this->variant->fresh()->stock);
    }

    public function test_el_comprobante_publico_no_se_abre_con_el_numero_de_pedido(): void
    {
        $order = $this->order();

        $this->get('/pedido/'.$order->id)->assertNotFound();
        $this->get('/pedido/'.$order->number)->assertNotFound();
        $this->get(route('order.public', $order))->assertOk()->assertSee($order->number);
    }

    public function test_la_tienda_no_muestra_el_costo_ni_prendas_archivadas(): void
    {
        $product = $this->variant->product;

        $this->get(route('product', $product))->assertOk()->assertSee('1,000.00')->assertDontSee('400.00');

        $product->update(['is_active' => false]);

        $this->get(route('product', $product))->assertNotFound();
        $this->get(route('catalog'))->assertOk()->assertDontSee('Vestido');
    }
}
