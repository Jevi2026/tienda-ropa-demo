<?php

namespace App\Support;

use App\Models\ProductVariant;
use Illuminate\Support\Collection;

/**
 * Carrito en sesión. Solo guarda variante y cantidad: precios y existencias se
 * leen siempre de la base de datos para que nadie pueda alterarlos.
 */
class Cart
{
    private const KEY = 'cart';

    /** @return array<int, int> */
    public function raw(): array
    {
        return session(self::KEY, []);
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    /** Agrega unidades sin pasarse de lo disponible y devuelve la cantidad final. */
    public function add(ProductVariant $variant, int $quantity): int
    {
        $items = $this->raw();
        $final = min(($items[$variant->id] ?? 0) + $quantity, $variant->stock);
        $items[$variant->id] = $final;
        session([self::KEY => array_filter($items)]);

        return $final;
    }

    public function set(ProductVariant $variant, int $quantity): int
    {
        $items = $this->raw();
        $final = min($quantity, $variant->stock);
        $items[$variant->id] = $final;
        session([self::KEY => array_filter($items)]);

        return $final;
    }

    public function remove(int $variantId): void
    {
        $items = $this->raw();
        unset($items[$variantId]);
        session([self::KEY => $items]);
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
    }

    /** @return Collection<int, object> */
    public function lines(): Collection
    {
        $raw = $this->raw();

        return ProductVariant::with('product')
            ->whereIn('id', array_keys($raw))
            ->get()
            ->filter(fn (ProductVariant $v) => $v->product->is_active)
            ->map(fn (ProductVariant $v) => (object) [
                'variant' => $v,
                'product' => $v->product,
                'quantity' => $raw[$v->id],
                'available' => $v->stock >= $raw[$v->id],
                'total' => $v->product->price * $raw[$v->id],
            ])
            ->values();
    }

    /** @return array<int, array{variant_id: int, quantity: int}> */
    public function orderLines(): array
    {
        return collect($this->raw())
            ->map(fn (int $quantity, int $id) => ['variant_id' => $id, 'quantity' => $quantity])
            ->values()
            ->all();
    }
}
