@extends('layouts.admin')

@section('title', 'Inventario')

@section('content')
    <div class="page-head">
        <h1>Inventario</h1>
        <a class="btn btn-primary" href="{{ route('admin.products.create') }}">@include('partials.icon', ['name' => 'plus']) Nueva prenda</a>
    </div>

    <div class="kpis" style="margin-bottom: 24px">
        <div class="kpi"><span class="kpi-label">Prendas activas</span><span class="kpi-value">{{ $activeCount }}</span></div>
        <div class="kpi"><span class="kpi-label">Unidades en existencia</span><span class="kpi-value">{{ number_format($totals->units) }}</span></div>
        <div class="kpi"><span class="kpi-label">Valor a costo</span><span class="kpi-value">{{ money($totals->cost_value) }}</span></div>
        <div class="kpi"><span class="kpi-label">Valor a precio de venta</span><span class="kpi-value">{{ money($totals->sale_value) }}</span></div>
    </div>

    <div class="row between" style="margin-bottom: 16px">
        <div class="pills">
            <a class="pill" href="{{ route('admin.products.index') }}" @if (! request('filtro')) aria-current="true" @endif>Todas</a>
            <a class="pill" href="{{ route('admin.products.index', ['filtro' => 'bajo']) }}" @if (request('filtro') === 'bajo') aria-current="true" @endif>Poco inventario <span class="pill-count">{{ $lowStockCount }}</span></a>
            <a class="pill" href="{{ route('admin.products.index', ['filtro' => 'archivados']) }}" @if (request('filtro') === 'archivados') aria-current="true" @endif>Archivadas</a>
        </div>
        <form class="search search-compact" role="search">
            @if (request('filtro'))
                <input type="hidden" name="filtro" value="{{ request('filtro') }}">
            @endif
            <label class="sr-only" for="q">Buscar prenda</label>
            <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Buscar prenda">
            <button class="search-submit" type="submit" aria-label="Buscar">@include('partials.icon', ['name' => 'arrow'])</button>
        </form>
    </div>

    <section class="card card-flush">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Prenda</th><th class="hide-sm">Categoría</th><th>Existencias por talla</th><th class="right">Total</th><th class="right">Precio</th></tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr class="clickable">
                            <td>
                                <div class="product-cell">
                                    @if ($product->imageSrc())
                                        <img src="{{ $product->imageSrc() }}" alt="" loading="lazy">
                                    @else
                                        <span class="thumb-empty"></span>
                                    @endif
                                    <div>
                                        <a class="strong" href="{{ route('admin.products.edit', $product) }}">{{ $product->name }}</a>
                                        @unless ($product->is_active)
                                            <div class="xsmall muted">Archivada</div>
                                        @endunless
                                    </div>
                                </div>
                            </td>
                            <td class="hide-sm nowrap">{{ $product->category->name }}</td>
                            <td>
                                <div class="size-stock">
                                    @foreach ($product->variants as $variant)
                                        <span class="tag {{ $variant->stock === 0 ? 'tag-out' : ($variant->stock <= $product->low_stock_at ? 'tag-low' : '') }}">{{ $variant->size }}: {{ $variant->stock }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="right num">{{ $product->stockTotal() }}</td>
                            <td class="right num nowrap">{{ money($product->price) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">No hay prendas con ese filtro.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{ $products->links('partials.pagination') }}
@endsection
