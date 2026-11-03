@extends('layouts.admin')

@section('title', 'Resumen')

@section('content')
    <div class="page-head">
        <div>
            <h1>Resumen de hoy</h1>
            <p class="small muted">{{ ucfirst(now()->locale('es')->isoFormat('dddd D [de] MMMM')) }}</p>
        </div>
        <div class="row">
            <a class="btn" href="{{ route('admin.products.create') }}">@include('partials.icon', ['name' => 'plus']) Nueva prenda</a>
            <a class="btn btn-primary" href="{{ route('admin.orders.create') }}">@include('partials.icon', ['name' => 'plus']) Nueva venta</a>
        </div>
    </div>

    <div class="kpis">
        <div class="kpi">
            <span class="kpi-label">Cobrado hoy</span>
            <span class="kpi-value">{{ money($summary['collected_total']) }}</span>
            <span class="kpi-note">{{ money($summary['cash_sales']) }} en efectivo</span>
        </div>
        <div class="kpi">
            <span class="kpi-label">Personas que compraron</span>
            <span class="kpi-value">{{ $summary['customers_count'] }}</span>
            <span class="kpi-note">{{ $summary['items_count'] }} prendas vendidas</span>
        </div>
        <div class="kpi">
            <span class="kpi-label">Pedidos de hoy</span>
            <span class="kpi-value">{{ $summary['orders_count'] }}</span>
            <span class="kpi-note">{{ $summary['orders_paid_count'] }} pagados · {{ $summary['orders_pending_count'] }} por cobrar</span>
        </div>
        <div class="kpi">
            <span class="kpi-label">Por cobrar en total</span>
            <span class="kpi-value">{{ money($summary['pending_balance']) }}</span>
            <span class="kpi-note">{{ $toCollectCount }} pedidos con saldo</span>
        </div>
    </div>

    <div class="panel-grid">
        <section class="card">
            <h2>Cobrado en los últimos 7 días</h2>
            <div class="chart" role="img" aria-label="Barras con lo cobrado por día. El detalle está en la tabla de abajo.">
                <div class="chart-grid" aria-hidden="true">
                    @foreach ([1, 0.5, 0] as $tick)
                        <div class="chart-gridline"><span>{{ money($chartMax * $tick) }}</span></div>
                    @endforeach
                </div>
                <div class="chart-bars">
                    @foreach ($days as $day)
                        <div class="chart-col {{ $day->is_today ? 'is-today' : '' }}" tabindex="0">
                            <div class="chart-plot">
                                <div class="chart-bar" style="height: {{ round($day->amount / $chartMax * 100, 2) }}%"></div>
                            </div>
                            <span class="chart-day">{{ $day->label }}</span>
                            <span class="chart-tip">{{ $day->long }}: <strong>{{ money($day->amount) }}</strong></span>
                        </div>
                    @endforeach
                </div>
            </div>
            <details class="data-table">
                <summary>Ver como tabla</summary>
                <table class="table">
                    <thead><tr><th>Día</th><th class="right">Cobrado</th></tr></thead>
                    <tbody>
                        @foreach ($days as $day)
                            <tr><td>{{ $day->long }}</td><td class="right num">{{ money($day->amount) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </details>
        </section>

        <section class="card">
            <div class="section-head" style="margin-bottom: 8px">
                <h2>Poco inventario</h2>
                <a class="section-link small" href="{{ route('admin.products.index', ['filtro' => 'bajo']) }}">{{ $lowStockCount }} prendas @include('partials.icon', ['name' => 'chevron'])</a>
            </div>
            @forelse ($lowStock as $product)
                <a class="list-row" href="{{ route('admin.products.edit', $product) }}">
                    <span class="list-row-title">{{ $product->name }}</span>
                    <span class="size-stock" style="justify-content: flex-end">
                        @foreach ($product->variants->where('stock', '<=', $product->low_stock_at) as $variant)
                            <span class="tag {{ $variant->stock === 0 ? 'tag-out' : 'tag-low' }}">{{ $variant->size }}: {{ $variant->stock }}</span>
                        @endforeach
                    </span>
                </a>
            @empty
                <p class="muted small">Todo el inventario está en buen nivel.</p>
            @endforelse
        </section>
    </div>

    <section class="card card-flush" style="margin-top: 16px">
        <div class="section-head" style="padding: 24px 24px 0; margin-bottom: 12px">
            <h2>Pedidos por cobrar</h2>
            <a class="section-link small" href="{{ route('admin.orders.index', ['estado' => 'pendiente']) }}">Ver todos @include('partials.icon', ['name' => 'chevron'])</a>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Pedido</th><th>Cliente</th><th class="hide-sm">Fecha</th><th>Estado</th><th class="right">Saldo</th></tr>
                </thead>
                <tbody>
                    @forelse ($toCollect as $order)
                        <tr class="clickable">
                            <td><a class="strong" href="{{ route('admin.orders.show', $order) }}">{{ $order->number }}</a></td>
                            <td>{{ $order->customer_name }}</td>
                            <td class="hide-sm nowrap">{{ $order->created_at->locale('es')->isoFormat('D MMM') }}</td>
                            <td>@include('partials.order-status')</td>
                            <td class="right num nowrap">{{ money($order->balance()) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">No hay pedidos pendientes de cobro.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
