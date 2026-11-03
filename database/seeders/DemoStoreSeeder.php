<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\CashRegister;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Datos ficticios para enseñar la demo. Los pedidos y cierres se generan con
 * los mismos servicios que usa la aplicación, moviendo el reloj día por día.
 */
class DemoStoreSeeder extends Seeder
{
    private const PHOTO = 'https://images.unsplash.com/photo-%s?auto=format&fit=crop&w=800&q=75';

    private const CUSTOMERS = [
        ['Andrea Mejía', '0000-0101'], ['Carlos Zelaya', '0000-0102'], ['Daniela Rivera', '0000-0103'],
        ['Fernando Pineda', '0000-0104'], ['Gabriela Núñez', '0000-0105'], ['Héctor Banegas', '0000-0106'],
        ['Karla Cáceres', '0000-0107'], ['Luis Ordóñez', '0000-0108'], ['Mariela Funes', '0000-0109'],
        ['Óscar Paz', '0000-0110'], ['Paola Murillo', '0000-0111'], ['Sofía Aguilar', '0000-0112'],
    ];

    public function run(): void
    {
        $this->seedCatalog();
        $this->seedSales();
    }

    private function seedCatalog(): void
    {
        $catalog = [
            'Mujer' => [
                ['Vestido rojo de vuelo', 1450, 680, '1595777457583-95e059d581b8', ['XS' => 6, 'S' => 8, 'M' => 8, 'L' => 5], true],
                ['Poncho tejido crema', 890, 390, '1434389677669-e08b4cac3105', ['S' => 7, 'M' => 9, 'L' => 6], true],
                ['Vestido celeste de playa', 1650, 760, '1539008835657-9e8e9680c956', ['S' => 5, 'M' => 6, 'L' => 3], false],
                ['Vestido floral con cinturón', 1290, 590, '1572804013309-59a88b7e92f1', ['S' => 8, 'M' => 2, 'L' => 6], false],
            ],
            'Hombre' => [
                ['Chaqueta de cuero', 3200, 1650, '1551028719-00167b16eac5', ['M' => 4, 'L' => 5, 'XL' => 3], true],
                ['Camisa azul de botones', 780, 340, '1596755094514-f87e34085b2c', ['S' => 7, 'M' => 10, 'L' => 8], false],
                ['Camisa de vestir', 950, 420, '1602810318383-e386cc2a3ccf', ['M' => 9, 'L' => 7, 'XL' => 2], false],
                ['Bomber terracota', 1850, 880, '1591047139829-d91aecb6caea', ['M' => 6, 'L' => 6, 'XL' => 4], true],
            ],
            'Camisetas' => [
                ['Camiseta blanca', 350, 130, '1521572163474-6864f9cf17ab', ['S' => 14, 'M' => 18, 'L' => 14], true],
                ['Camiseta negra estampada', 390, 150, '1503341504253-dff4815485f1', ['S' => 12, 'M' => 16, 'L' => 12], false],
                ['Camiseta gráfica beige', 420, 165, '1576566588028-4147f3842f27', ['S' => 10, 'M' => 12, 'L' => 3], false],
                ['Camiseta negra básica', 350, 130, '1583743814966-8936f5b7be1a', ['S' => 9, 'M' => 11, 'L' => 9], false],
            ],
            'Jeans y pantalones' => [
                ['Jeans clásicos', 1190, 520, '1542272604-787c3835535d', ['28' => 6, '30' => 9, '32' => 9, '34' => 5], false],
                ['Jean con parches', 1090, 480, '1541099649105-f69ad21f3246', ['28' => 7, '30' => 8, '32' => 2], false],
                ['Pantalón azul oscuro', 1150, 500, '1624378439575-d8705ad7ae80', ['30' => 6, '32' => 8, '34' => 5], false],
                ['Jogger rosa palo', 1320, 610, '1594633312681-425c7b97ccd1', ['30' => 5, '32' => 6, '34' => 4], false],
            ],
        ];

        foreach (array_keys($catalog) as $position => $name) {
            $category = Category::create(['name' => $name, 'slug' => Str::slug($name), 'position' => $position]);

            foreach ($catalog[$name] as [$title, $price, $cost, $photo, $sizes, $featured]) {
                $product = Product::create([
                    'category_id' => $category->id,
                    'name' => $title,
                    'slug' => Product::uniqueSlug($title),
                    'description' => 'Prenda de ejemplo para esta demo. Aquí la tienda describe el corte, la tela y los cuidados de la prenda.',
                    'price' => $price * 100,
                    'cost' => $cost * 100,
                    'image_url' => sprintf(self::PHOTO, $photo),
                    'is_featured' => $featured,
                ]);

                foreach ($sizes as $size => $stock) {
                    $variant = $product->variants()->create(['size' => (string) $size, 'stock' => $stock]);

                    StockMovement::create([
                        'product_variant_id' => $variant->id,
                        'type' => 'entrada',
                        'quantity' => $stock,
                        'note' => 'Inventario inicial',
                    ]);
                }
            }
        }
    }

    private function seedSales(): void
    {
        mt_srand(2026);

        $orders = app(OrderService::class);
        $cash = app(CashRegister::class);
        $adminId = User::query()->value('id');
        $realNow = Carbon::now();
        $methods = ['efectivo', 'efectivo', 'transferencia', 'tarjeta', 'tigo_money'];

        for ($back = 7; $back >= 0; $back--) {
            $isToday = $back === 0;
            $day = $realNow->copy()->subDays($back);
            $sales = $isToday ? 5 : mt_rand(4, 8);

            for ($n = 0; $n < $sales; $n++) {
                // Hoy se reparte hacia atrás para no crear ventas en el futuro.
                $moment = $isToday
                    ? $realNow->copy()->subMinutes(($sales - $n) * 3)->max($day->copy()->startOfDay())
                    : $day->copy()->setTime(9 + $n, mt_rand(0, 59));

                Carbon::setTestNow($moment);

                $order = $this->fakeOrder($orders, $n, $adminId);

                if (! $order) {
                    continue;
                }

                $this->fakePayment($orders, $order, $n, $back, $methods, $adminId);
            }

            if (! $isToday) {
                Carbon::setTestNow($day->copy()->setTime(19, 30));

                if ($back === 3) {
                    $cash->addMovement('gasto', 'Bolsas y etiquetas', 45000, $adminId);
                }

                $opening = $cash->defaultOpening($day->toDateString()) ?: 200000;
                $expected = $cash->expectedCash($cash->summary($day->toDateString()), $opening);

                // Cada noche se deposita lo que pase del fondo de caja.
                if ($expected > 200000) {
                    $cash->addMovement('retiro', 'Depósito al banco', $expected - 200000, $adminId);
                    $expected = 200000;
                }

                // Un día con faltante pequeño para que se vea cómo lo reporta el cierre.
                $cash->close($day->toDateString(), $opening, $back === 4 ? $expected - 5000 : $expected, $back === 4 ? 'Faltante por revisar.' : null, $adminId);
            }
        }

        Carbon::setTestNow();
    }

    private function fakeOrder(OrderService $orders, int $n, ?int $adminId): ?Order
    {
        $variants = Product::with(['variants' => fn ($q) => $q->where('stock', '>', 2)])->get()->flatMap->variants->shuffle(mt_rand());

        if ($variants->isEmpty()) {
            return null;
        }

        $lines = $variants->take(mt_rand(1, 3))->map(fn ($v) => ['variant_id' => $v->id, 'quantity' => 1])->values()->all();
        $isWalkIn = $n % 3 === 2;
        [$name, $phone] = self::CUSTOMERS[mt_rand(0, count(self::CUSTOMERS) - 1)];

        return $isWalkIn
            ? $orders->create(['customer_name' => Order::WALK_IN_NAME], $lines, 'tienda', $adminId)
            : $orders->create([
                'customer_name' => $name,
                'customer_phone' => $phone,
                'delivery_method' => $n % 2 ? 'envio' : 'retiro',
                'address' => $n % 2 ? 'Col. Palmira, Tegucigalpa' : null,
                'payment_preference' => 'transferencia',
            ], $lines, 'web');
    }

    /** La mayoría paga completo; algunos abonan o quedan pendientes para llenar la cartera. */
    private function fakePayment(OrderService $orders, Order $order, int $n, int $back, array $methods, ?int $adminId): void
    {
        $roll = mt_rand(1, 10);
        $method = $methods[mt_rand(0, count($methods) - 1)];

        // Hoy siempre deja un pedido sin pagar y otro abonado para enseñar el control de cobros.
        if ($back === 0 && $n === 0) {
            return;
        }

        if ($back === 0 && $n === 1) {
            $orders->registerPayment($order, (int) round($order->total / 2, -2), $method, 'Abono', $adminId);

            return;
        }

        if ($order->channel === 'tienda') {
            $orders->registerPayment($order, $order->total, $method, null, $adminId);
        } elseif ($roll <= 6) {
            $orders->registerPayment($order, $order->total, $method, null, $adminId);
            $order->update(['delivered_at' => now()]);
        } elseif ($roll <= 8) {
            $orders->registerPayment($order, (int) round($order->total / 2, -2), $method, 'Abono', $adminId);
        }
    }
}
