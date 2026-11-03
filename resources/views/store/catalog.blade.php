@extends('layouts.store')

@section('title', $category?->name ?? 'Catálogo')

@section('content')
    <div class="page-head">
        <h1>{{ $category?->name ?? 'Catálogo' }}</h1>
        <form class="search search-compact" action="{{ route('catalog') }}" role="search">
            @if ($category)
                <input type="hidden" name="categoria" value="{{ $category->slug }}">
            @endif
            <label class="sr-only" for="q">Buscar ropa</label>
            <input id="q" name="q" type="search" value="{{ $term }}" placeholder="Buscar ropa">
            <button class="search-submit" type="submit" aria-label="Buscar">@include('partials.icon', ['name' => 'arrow'])</button>
        </form>
    </div>

    <div class="pills" style="margin-bottom: 24px">
        <a class="pill" href="{{ route('catalog') }}" @if (! $category) aria-current="true" @endif>Todo</a>
        @foreach ($categories as $item)
            <a class="pill" href="{{ route('catalog', ['categoria' => $item->slug]) }}" @if ($category?->id === $item->id) aria-current="true" @endif>{{ $item->name }}</a>
        @endforeach
    </div>

    @if ($products->isEmpty())
        <div class="card empty">
            <h2>No encontramos esa prenda</h2>
            <p>Prueba con otra palabra o mira todo el catálogo.</p>
            <a class="btn" href="{{ route('catalog') }}">Ver todo</a>
        </div>
    @else
        <div class="product-grid">
            @foreach ($products as $product)
                @include('partials.product-card')
            @endforeach
        </div>
        {{ $products->links('partials.pagination') }}
    @endif
@endsection
