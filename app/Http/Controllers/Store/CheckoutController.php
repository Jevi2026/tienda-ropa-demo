<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Support\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function show(Cart $cart): View|RedirectResponse
    {
        $lines = $cart->lines();

        if ($lines->isEmpty()) {
            return redirect()->route('cart');
        }

        return view('store.checkout', [
            'lines' => $lines,
            'total' => $lines->sum('total'),
        ]);
    }

    public function store(Request $request, Cart $cart, OrderService $orders): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:40', 'regex:/^[0-9+()\-\s]{8,}$/'],
            'delivery_method' => ['required', Rule::in(['retiro', 'envio'])],
            'address' => ['nullable', 'required_if:delivery_method,envio', 'string', 'max:255'],
            'payment_preference' => ['required', Rule::in(array_keys(config('tienda.metodos_pago')))],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($cart->count() === 0) {
            return redirect()->route('cart');
        }

        $order = $orders->create($data, $cart->orderLines(), 'web');

        $cart->clear();

        return redirect()->route('order.public', $order);
    }

    public function confirmation(Order $order): View
    {
        $order->load('items');

        $whatsapp = config('tienda.whatsapp')
            ? whatsapp_url(config('tienda.whatsapp'), "Hola, hice el pedido {$order->number} por ".money($order->total).'.')
            : null;

        return view('store.order', compact('order', 'whatsapp'));
    }
}
