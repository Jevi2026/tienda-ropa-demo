@extends('layouts.base')

@section('body-class', config('tienda.demo') ? 'has-band' : '')

@section('body')
    @if (config('tienda.demo'))
        <div class="demo-band">
            <span>Tienda de demostración: los productos y pedidos no son reales.</span>
            <a href="{{ route('admin.dashboard') }}">Ver el panel de administración</a>
        </div>
    @endif

    <nav class="rail" aria-label="Principal">
        <a class="rail-logo" href="{{ route('home') }}" aria-label="{{ config('tienda.nombre') }}, inicio">{{ mb_strtolower(config('tienda.nombre')) }}<span>.</span></a>
        <a class="rail-link" href="{{ route('home') }}" @if (request()->routeIs('home')) aria-current="page" @endif>
            @include('partials.icon', ['name' => 'home'])Inicio
        </a>
        <a class="rail-link" href="{{ route('catalog') }}" @if (request()->routeIs('catalog', 'product')) aria-current="page" @endif>
            @include('partials.icon', ['name' => 'shirt'])Catálogo
        </a>
        <a class="rail-link" href="{{ route('cart') }}" @if (request()->routeIs('cart', 'checkout')) aria-current="page" @endif>
            @include('partials.icon', ['name' => 'bag'])
            @if ($cartCount)
                <span class="rail-count">{{ $cartCount }}<span class="sr-only"> en el carrito</span></span>
            @endif
            Carrito
        </a>
    </nav>

    <main class="main">
        <div class="page">
            @include('partials.flash')
            @yield('content')
        </div>
    </main>
@endsection
