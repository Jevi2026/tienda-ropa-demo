@extends('layouts.base')

@section('noindex', '1')

@section('body')
    <nav class="rail" aria-label="Panel de administración">
        <a class="rail-logo" href="{{ route('home') }}" aria-label="Ver la tienda">{{ mb_strtolower(config('tienda.nombre')) }}<span>.</span></a>
        <a class="rail-link" href="{{ route('admin.dashboard') }}" @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif>
            @include('partials.icon', ['name' => 'home'])Resumen
        </a>
        <a class="rail-link" href="{{ route('admin.products.index') }}" @if (request()->routeIs('admin.products.*')) aria-current="page" @endif>
            @include('partials.icon', ['name' => 'shirt'])Inventario
        </a>
        <a class="rail-link" href="{{ route('admin.orders.index') }}" @if (request()->routeIs('admin.orders.*')) aria-current="page" @endif>
            @include('partials.icon', ['name' => 'receipt'])Pedidos
        </a>
        <a class="rail-link" href="{{ route('admin.cash.index') }}" @if (request()->routeIs('admin.cash.*')) aria-current="page" @endif>
            @include('partials.icon', ['name' => 'cash'])Caja
        </a>
        <span class="rail-spacer"></span>
        <a class="rail-link" href="{{ route('home') }}">
            @include('partials.icon', ['name' => 'store'])Tienda
        </a>
        <form method="post" action="{{ route('admin.logout') }}">
            @csrf
            <button class="rail-link" type="submit">@include('partials.icon', ['name' => 'logout'])Salir</button>
        </form>
    </nav>

    <main class="main">
        <div class="page">
            @include('partials.flash')
            @yield('content')
        </div>
    </main>
@endsection
