<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('estado');
        $term = trim((string) $request->query('q'));

        $orders = Order::withStatus($status)
            ->withSum('items as units', 'quantity')
            ->when($term !== '', fn (Builder $q) => $q->where(function (Builder $q) use ($term) {
                $q->whereLike('number', "%{$term}%")
                    ->orWhereLike('customer_name', "%{$term}%")
                    ->orWhereLike('customer_phone', "%{$term}%");
            }))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'status' => $status,
            'term' => $term,
            'counts' => [
                'todos' => Order::count(),
                'pendiente' => Order::withStatus('pendiente')->count(),
                'parcial' => Order::withStatus('parcial')->count(),
                'pagado' => Order::withStatus('pagado')->count(),
                'cancelado' => Order::withStatus('cancelado')->count(),
            ],
            'toCollect' => (int) Order::withStatus('por_cobrar')->sum(DB::raw('total - amount_paid')),
        ]);
    }

    public function create(): View
    {
        return view('admin.orders.create', [
            'products' => Product::active()
                ->with(['variants' => fn ($q) => $q->where('stock', '>', 0)])
                ->orderBy('name')
                ->get()
                ->filter(fn (Product $p) => $p->variants->isNotEmpty()),
        ]);
    }

    public function store(Request $request, OrderService $orders): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'delivery_method' => ['required', Rule::in(['retiro', 'envio'])],
            'address' => ['nullable', 'required_if:delivery_method,envio', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'payment_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'payment_method' => ['nullable', 'required_with:payment_amount', Rule::in(array_keys(config('tienda.metodos_pago')))],
        ], [
            'items.required' => 'Agrega al menos una prenda.',
            'items.*.variant_id.required' => 'Elige la prenda en cada línea.',
        ]);

        $customer = [
            'customer_name' => filled($data['customer_name'] ?? null) ? $data['customer_name'] : Order::WALK_IN_NAME,
            'customer_phone' => $data['customer_phone'] ?? null,
            'delivery_method' => $data['delivery_method'],
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];

        // Si el pago falla (caja cerrada, monto de más) tampoco se crea el pedido.
        $order = DB::transaction(function () use ($orders, $customer, $data, $request) {
            $order = $orders->create($customer, $data['items'], 'tienda', $request->user()->id);

            $amount = to_cents($data['payment_amount'] ?? 0);

            if ($amount > 0) {
                $orders->registerPayment($order, $amount, $data['payment_method'], null, $request->user()->id);
            }

            return $order;
        });

        return redirect()->route('admin.orders.show', $order)->with('status', "Venta {$order->number} registrada.");
    }

    public function show(Order $order): View
    {
        $order->load('items', 'payments');

        return view('admin.orders.show', [
            'order' => $order,
            'whatsapp' => whatsapp_url($order->customer_phone, "Hola {$order->customer_name}, te escribimos por tu pedido {$order->number}."),
        ]);
    }

    public function pay(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'method' => ['required', Rule::in(array_keys(config('tienda.metodos_pago')))],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $orders->registerPayment($order, to_cents($data['amount']), $data['method'], $data['note'] ?? null, $request->user()->id);

        return back()->with('status', 'Pago registrado.');
    }

    public function deliver(Order $order): RedirectResponse
    {
        if ($order->cancelled_at) {
            return back()->withErrors(['order' => 'Este pedido está cancelado.']);
        }

        $order->update(['delivered_at' => $order->delivered_at ? null : now()]);

        return back()->with('status', $order->delivered_at ? 'Pedido marcado como entregado.' : 'Entrega desmarcada.');
    }

    public function cancel(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        $orders->cancel($order, $request->user()->id);

        return back()->with('status', 'Pedido cancelado. La ropa volvió al inventario.');
    }
}
