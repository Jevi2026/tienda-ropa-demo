@extends('layouts.admin')

@section('title', 'Pedidos')

@section('content')
    <div class="page-head">
        <div>
            <h1>Pedidos</h1>
            <p class="small muted">Por cobrar en total: <span class="semibold num">{{ money($toCollect) }}</span></p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.orders.create') }}">@include('partials.icon', ['name' => 'plus']) Nueva venta</a>
    </div>

    <div class="row between" style="margin-bottom: 16px">
        <div class="pills">
            <a class="pill" href="{{ route('admin.orders.index') }}" @if (! $status) aria-current="true" @endif>Todos <span class="pill-count">{{ $counts['todos'] }}</span></a>
            @foreach (\App\Models\Order::STATUS_LABELS as $key => $label)
                <a class="pill" href="{{ route('admin.orders.index', ['estado' => $key]) }}" @if ($status === $key) aria-current="true" @endif>{{ $label }} <span class="pill-count">{{ $counts[$key] }}</span></a>
            @endforeach
        </div>
        <form class="search search-compact" role="search">
            @if ($status)
                <input type="hidden" name="estado" value="{{ $status }}">
            @endif
            <label class="sr-only" for="q">Buscar pedido</label>
            <input id="q" name="q" type="search" value="{{ $term }}" placeholder="Número, nombre o teléfono">
            <button class="search-submit" type="submit" aria-label="Buscar">@include('partials.icon', ['name' => 'arrow'])</button>
        </form>
    </div>

    <section class="card card-flush">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Pedido</th><th>Cliente</th><th class="hide-sm">Fecha</th><th class="hide-sm">Canal</th>
                        <th class="hide-sm">Entrega</th><th>Estado</th><th class="right hide-sm">Total</th><th class="right">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr class="clickable">
                            <td><a class="strong" href="{{ route('admin.orders.show', $order) }}">{{ $order->number }}</a></td>
                            <td>{{ $order->customer_name }}</td>
                            <td class="hide-sm nowrap">{{ $order->created_at->locale('es')->isoFormat('D MMM, h:mm a') }}</td>
                            <td class="hide-sm">{{ $order->channel === 'web' ? 'En línea' : 'Mostrador' }}</td>
                            <td class="hide-sm">{{ $order->delivered_at ? 'Entregado' : 'Por entregar' }}</td>
                            <td>@include('partials.order-status')</td>
                            <td class="right num nowrap hide-sm">{{ money($order->total) }}</td>
                            <td class="right num nowrap">{{ money($order->balance()) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="muted">No hay pedidos con ese filtro.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{ $orders->links('partials.pagination') }}
@endsection
