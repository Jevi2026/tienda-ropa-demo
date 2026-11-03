@extends('layouts.store')

@section('title', 'Ropa para todos los días')
@section('description', 'Vestidos, camisas, camisetas y jeans. Compra en línea y retira en tienda o recibe a domicilio.')

@section('content')
    @php
        // Posición de cada tarjeta flotante: izquierda, arriba, ancho y giro.
        $positions = [
            ['4%', '26px', '168px', '-5deg'],
            ['22%', '4px', '196px', '3deg'],
            ['42%', '30px', '172px', '-2deg'],
            ['60%', '2px', '188px', '4deg'],
            ['79%', '34px', '164px', '-4deg'],
        ];
    @endphp

    <section class="hero">
        <div class="constellation" aria-label="Prendas destacadas">
            @foreach ($featured as $i => $product)
                <a class="float-card" href="{{ route('product', $product) }}"
                   style="--x: {{ $positions[$i][0] }}; --y: {{ $positions[$i][1] }}; --w: min({{ $positions[$i][2] }}, 32%); --r: {{ $positions[$i][3] }}; z-index: {{ $i % 2 + 1 }}">
                    <div class="photo">
                        <img src="{{ $product->imageSrc() }}" alt="{{ $product->name }}">
                    </div>
                    <span class="float-card-name">{{ $product->name }}</span>
                    <span class="float-card-price num">{{ money($product->price) }}</span>
                </a>
            @endforeach
        </div>

        <h1 class="wordmark">{{ mb_strtolower(config('tienda.nombre')) }}</h1>
        <p class="hero-lead">Ropa para todos los días. Retira en tienda o recibe a domicilio.</p>

        <form class="search" action="{{ route('catalog') }}" role="search">
            <label class="sr-only" for="q">Buscar ropa</label>
            <input id="q" name="q" type="search" placeholder="¿Qué estás buscando hoy?">
            <button class="search-submit" type="submit" aria-label="Buscar">@include('partials.icon', ['name' => 'arrow'])</button>
        </form>

        <div class="pills">
            @foreach ($categories as $category)
                <a class="pill" href="{{ route('catalog', ['categoria' => $category->slug]) }}">
                    @if ($covers[$category->id] ?? null)
                        <img src="{{ $covers[$category->id] }}" alt="" loading="lazy">
                    @endif
                    {{ $category->name }}
                </a>
            @endforeach
        </div>
    </section>

    @foreach ($sections as $section)
        <section class="section">
            <div class="section-head">
                <h2>{{ $section->category->name }}</h2>
                <a class="section-link small" href="{{ route('catalog', ['categoria' => $section->category->slug]) }}">
                    Ver todo @include('partials.icon', ['name' => 'chevron'])
                </a>
            </div>
            <div class="product-grid">
                @foreach ($section->products as $product)
                    @include('partials.product-card')
                @endforeach
            </div>
        </section>
    @endforeach
@endsection
