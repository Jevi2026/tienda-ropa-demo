@extends('layouts.admin')

@section('title', 'Pedido '.$order->number)

@section('content')
    <div class="page-head">
        <div>
            <a class="small muted" href="{{ route('admin.orders.index') }}">Pedidos</a>
            <h1>{{ $order->number }}</h1>
            <p class="small muted">{{ $order->created_at->locale('es')->isoFormat('dddd D [de] MMMM, h:mm a') }} · {{ $order->channel === 'web' ? 'En línea' : 'Mostrador' }}</p>
        </div>
        <div class="row">
            @include('partials.order-status')
            @if ($order->delivered_at && ! $order->cancelled_at)
                <span class="tag">Entregado</span>
            @endif
        </div>
    </div>

    <div class="split">
        <div class="stack-lg">
            <section class="card">
                <h2 class="card-title">Prendas</h2>
                @foreach ($order->items as $item)
                    <div class="list-row">
                        <span>{{ $item->quantity }} × {{ $item->product_name }} <span class="muted">({{ $item->size }})</span></span>
                        <span class="num nowrap">{{ money($item->line_total) }}</span>
                    </div>
                @endforeach
                <div class="totals" style="margin-top: 12px">
                    <div class="totals-row grand"><span>Total</span><span class="num">{{ money($order->total) }}</span></div>
                    <div class="totals-row small"><span class="muted">Pagado</span><span class="num">{{ money($order->amount_paid) }}</span></div>
                    <div class="totals-row semibold"><span>Saldo</span><span class="num">{{ money($order->balance()) }}</span></div>
                </div>
            </section>

            <section class="card card-flush">
                <h2 style="padding: 24px 24px 12px">Pagos</h2>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Fecha</th><th>Método</th><th class="hide-sm">Nota</th><th class="right">Monto</th></tr></thead>
                        <tbody>
                            @forelse ($order->payments as $payment)
                                <tr>
                                    <td class="nowrap">{{ $payment->paid_at->locale('es')->isoFormat('D MMM, h:mm a') }}</td>
                                    <td>{{ $payment->methodLabel() }}</td>
                                    <td class="hide-sm muted">{{ $payment->note }}</td>
                                    <td class="right num nowrap">{{ money($payment->amount) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="muted">Todavía no hay pagos registrados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <aside class="stack-lg">
            @if ($order->balance() > 0)
                <section class="card">
                    <h2 class="card-title">Registrar pago</h2>
                    <form class="stack" method="post" action="{{ route('admin.orders.pay', $order) }}">
                        @csrf
                        <div class="field">
                            <label for="amount">Monto ({{ config('tienda.moneda') }})</label>
                            <input class="input num" id="amount" name="amount" type="number" step="0.01" min="0.01" max="{{ from_cents($order->balance()) }}" value="{{ old('amount', from_cents($order->balance())) }}" required>
                            <span class="hint">Puedes cobrar una parte como abono.</span>
                        </div>
                        <div class="field">
                            <label for="method">Método</label>
                            <select class="select" id="method" name="method">
                                @foreach (config('tienda.metodos_pago') as $key => $label)
                                    <option value="{{ $key }}" @selected(old('method', $order->payment_preference) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn btn-primary btn-block" type="submit" data-submit-once>Marcar como cobrado</button>
                    </form>
                </section>
            @endif

            <section class="card stack">
                <h2>Cliente</h2>
                <div class="small">
                    <p class="semibold">{{ $order->customer_name }}</p>
                    @if ($order->customer_phone)
                        <p>{{ $order->customer_phone }}</p>
                    @endif
                    <p class="muted" style="margin-top: 8px">{{ $order->delivery_method === 'envio' ? 'Envío a domicilio' : 'Retira en tienda' }}</p>
                    @if ($order->address)
                        <p>{{ $order->address }}</p>
                    @endif
                    @if ($order->payment_preference)
                        <p class="muted">Quiere pagar con {{ config('tienda.metodos_pago')[$order->payment_preference] ?? $order->payment_preference }}</p>
                    @endif
                    @if ($order->notes)
                        <p style="margin-top: 8px">“{{ $order->notes }}”</p>
                    @endif
                </div>
                @if ($whatsapp)
                    <a class="btn btn-block" href="{{ $whatsapp }}" target="_blank" rel="noopener">@include('partials.icon', ['name' => 'chat']) Escribir por WhatsApp</a>
                @endif
            </section>

            @unless ($order->cancelled_at)
                <section class="card stack">
                    <h2>Acciones</h2>
                    <form method="post" action="{{ route('admin.orders.deliver', $order) }}">
                        @csrf
                        <button class="btn btn-block" type="submit">{{ $order->delivered_at ? 'Desmarcar entrega' : 'Marcar como entregado' }}</button>
                    </form>
                    <form method="post" action="{{ route('admin.orders.cancel', $order) }}"
                          data-confirm="¿Cancelar este pedido? La ropa vuelve al inventario{{ $order->amount_paid > 0 ? ' y se registra la devolución de '.money($order->amount_paid) : '' }}.">
                        @csrf
                        <button class="btn btn-quiet btn-block" type="submit">Cancelar pedido</button>
                    </form>
                </section>
            @endunless
        </aside>
    </div>
@endsection
