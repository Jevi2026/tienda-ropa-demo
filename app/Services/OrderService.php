<?php

namespace App\Services;

use App\Models\CashClosing;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Todo lo que mueve inventario y dinero pasa por aquí, para que la tienda en
 * línea y la venta de mostrador sigan exactamente las mismas reglas.
 */
class OrderService
{
    /**
     * @param  array<string, mixed>  $customer
     * @param  array<int, array{variant_id: int, quantity: int}>  $lines
     */
    public function create(array $customer, array $lines, string $channel, ?int $userId = null): Order
    {
        $quantities = [];

        foreach ($lines as $line) {
            $id = (int) $line['variant_id'];
            $quantities[$id] = ($quantities[$id] ?? 0) + (int) $line['quantity'];
        }

        if ($quantities === []) {
            throw ValidationException::withMessages(['items' => 'El pedido no tiene productos.']);
        }

        return DB::transaction(function () use ($customer, $quantities, $channel, $userId) {
            $variants = ProductVariant::with('product')
                ->whereIn('id', array_keys($quantities))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $total = 0;
            $items = [];

            foreach ($quantities as $id => $quantity) {
                $variant = $variants->get($id);

                if (! $variant || ! $variant->product->is_active) {
                    throw ValidationException::withMessages(['items' => 'Uno de los productos ya no está disponible.']);
                }

                if ($quantity < 1 || $variant->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => "Solo quedan {$variant->stock} de {$variant->product->name} talla {$variant->size}.",
                    ]);
                }

                $lineTotal = $variant->product->price * $quantity;
                $total += $lineTotal;

                $items[] = [
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'size' => $variant->size,
                    'unit_price' => $variant->product->price,
                    'unit_cost' => $variant->product->cost,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                ];
            }

            $order = Order::create([
                'public_token' => Str::random(40),
                'channel' => $channel,
                'customer_name' => $customer['customer_name'] ?? Order::WALK_IN_NAME,
                'customer_phone' => $customer['customer_phone'] ?? null,
                'delivery_method' => $customer['delivery_method'] ?? 'retiro',
                'address' => $customer['address'] ?? null,
                'payment_preference' => $customer['payment_preference'] ?? null,
                'notes' => $customer['notes'] ?? null,
                'total' => $total,
                'created_by' => $userId,
            ]);

            $order->update(['number' => 'P-'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT)]);
            $order->items()->createMany($items);

            foreach ($items as $item) {
                $variants[$item['product_variant_id']]->decrement('stock', $item['quantity']);

                StockMovement::create([
                    'product_variant_id' => $item['product_variant_id'],
                    'order_id' => $order->id,
                    'user_id' => $userId,
                    'type' => 'venta',
                    'quantity' => -$item['quantity'],
                    'note' => "Pedido {$order->number}",
                ]);
            }

            return $order;
        });
    }

    public function registerPayment(Order $order, int $amount, string $method, ?string $note = null, ?int $userId = null): Payment
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'El monto debe ser mayor que cero.']);
        }

        return DB::transaction(function () use ($order, $amount, $method, $note, $userId) {
            $this->ensureCashIsOpen();

            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->cancelled_at) {
                throw ValidationException::withMessages(['amount' => 'Este pedido está cancelado.']);
            }

            if ($amount > $locked->balance()) {
                throw ValidationException::withMessages([
                    'amount' => 'El pago supera el saldo pendiente de '.money($locked->balance()).'.',
                ]);
            }

            $payment = $locked->payments()->create([
                'amount' => $amount,
                'method' => $method,
                'note' => $note,
                'paid_at' => now(),
                'user_id' => $userId,
            ]);

            $locked->increment('amount_paid', $amount);

            return $payment;
        });
    }

    /** Cancela el pedido, devuelve la ropa al inventario y reversa lo cobrado. */
    public function cancel(Order $order, ?int $userId = null): void
    {
        DB::transaction(function () use ($order, $userId) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->cancelled_at) {
                throw ValidationException::withMessages(['order' => 'Este pedido ya estaba cancelado.']);
            }

            $collected = $locked->payments()->get()->groupBy('method')->map(fn ($p) => (int) $p->sum('amount'))->filter(fn ($net) => $net > 0);

            if ($collected->isNotEmpty()) {
                $this->ensureCashIsOpen();
            }

            foreach ($collected as $method => $net) {
                $locked->payments()->create([
                    'amount' => -$net,
                    'method' => $method,
                    'note' => 'Devolución por cancelación',
                    'paid_at' => now(),
                    'user_id' => $userId,
                ]);
            }

            foreach ($locked->items()->with('variant')->get() as $item) {
                if (! $item->variant) {
                    continue;
                }

                $item->variant->increment('stock', $item->quantity);

                StockMovement::create([
                    'product_variant_id' => $item->product_variant_id,
                    'order_id' => $locked->id,
                    'user_id' => $userId,
                    'type' => 'devolucion',
                    'quantity' => $item->quantity,
                    'note' => "Cancelación de {$locked->number}",
                ]);
            }

            $locked->update(['cancelled_at' => now(), 'amount_paid' => 0]);
        });
    }

    private function ensureCashIsOpen(): void
    {
        if (CashClosing::where('day', now()->toDateString())->exists()) {
            throw ValidationException::withMessages([
                'amount' => 'La caja de hoy ya está cerrada. Reábrela para registrar movimientos de dinero.',
            ]);
        }
    }
}
