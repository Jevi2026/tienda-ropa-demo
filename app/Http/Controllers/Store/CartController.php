<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Support\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function show(Cart $cart): View
    {
        $lines = $cart->lines();

        return view('store.cart', [
            'lines' => $lines,
            'total' => $lines->sum('total'),
        ]);
    }

    public function add(Request $request, Cart $cart): RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:50'],
        ], [
            'variant_id.required' => 'Elige una talla.',
        ]);

        $variant = ProductVariant::with('product')->findOrFail($data['variant_id']);

        abort_unless($variant->product->is_active, 404);

        if ($variant->stock < 1) {
            return back()->withErrors(['variant_id' => 'Esa talla se agotó.']);
        }

        $cart->add($variant, (int) ($data['quantity'] ?? 1));

        return redirect()->route('cart')->with('status', "{$variant->product->name} talla {$variant->size} se agregó al carrito.");
    }

    public function update(Request $request, ProductVariant $variant, Cart $cart): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:50']]);

        $final = $cart->set($variant, $data['quantity']);

        return back()->with('status', $final < $data['quantity']
            ? "Solo hay {$variant->stock} disponibles de esa talla."
            : 'Carrito actualizado.');
    }

    public function remove(int $variantId, Cart $cart): RedirectResponse
    {
        $cart->remove($variantId);

        return back()->with('status', 'Producto eliminado del carrito.');
    }
}
