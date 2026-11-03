@extends('layouts.store')

@section('title', 'Pedido '.$order->number)
@section('noindex', '1')

@section('content')
    <div class="card stack-lg" style="max-width: 640px; margin: 0 auto">
        <div class="stack">
            <p class="small muted">Pedido {{ $order->number }}</p>
            <h1>¡Gracias, {{ \Illuminate\Support\Str::of($order->customer_name)->before(' ') }}!</h1>
            <p class="muted">Recibimos tu pedido y apartamos tus prendas. La tienda te escribirá para confirmar el pago y la entrega.</p>
            <div>@include('partials.order-status')</div>
        </div>

        <div>
            @foreach ($order->items as $item)
                <div class="list-row">
                    <span>{{ $item->quantity }} × {{ $item->product_name }} <span class="muted">({{ $item->size }})</span></span>
                    <span class="num nowrap">{{ money($item->line_total) }}</span>
                </div>
            @endforeach
        </div>
        <div class="totals-row grand totals"><span>Total</span><span class="num">{{ money($order->total) }}</span></div>

        <div class="row">
            @if ($whatsapp)
                <a class="btn btn-primary" href="{{ $whatsapp }}" target="_blank" rel="noopener">@include('partials.icon', ['name' => 'chat']) Avisar por WhatsApp</a>
            @endif
            <a class="btn" href="{{ route('catalog') }}">Seguir comprando</a>
        </div>
    </div>
@endsection
