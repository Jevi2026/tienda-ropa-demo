@extends('layouts.store')

@section('title', 'Carrito')

@section('content')
    <div class="page-head"><h1>Tu carrito</h1></div>

    @if ($lines->isEmpty())
        <div class="card empty">
            <h2>Todavía no hay nada aquí</h2>
            <p>Cuando elijas una prenda aparecerá en esta lista.</p>
            <a class="btn" href="{{ route('catalog') }}">Ver el catálogo</a>
        </div>
    @else
        <div class="split">
            <div class="card">
                @foreach ($lines as $line)
                    <div class="line-item">
                        <div class="line-thumb">
                            @if ($line->product->imageSrc())
                                <img src="{{ $line->product->imageSrc() }}" alt="">
                            @endif
                        </div>
                        <div>
                            <a class="semibold small" style="text-decoration: none" href="{{ route('product', $line->product) }}">{{ $line->product->name }}</a>
                            <div class="small muted">Talla {{ $line->variant->size }} · {{ money($line->product->price) }}</div>
                            @unless ($line->available)
                                <div class="field-error" style="margin-top: 4px">Solo quedan {{ $line->variant->stock }}.</div>
                            @endunless
                        </div>
                        <div class="row" style="gap: 8px">
                            <form method="post" action="{{ route('cart.update', $line->variant) }}" class="row" style="gap: 8px">
                                @csrf
                                @method('patch')
                                <label class="sr-only" for="qty-{{ $line->variant->id }}">Cantidad de {{ $line->product->name }}</label>
                                <input class="input qty-input" id="qty-{{ $line->variant->id }}" type="number" name="quantity" min="0" max="50" value="{{ $line->quantity }}" onchange="this.form.submit()">
                                <noscript><button class="btn" type="submit">Actualizar</button></noscript>
                            </form>
                            <form method="post" action="{{ route('cart.remove', $line->variant->id) }}">
                                @csrf
                                @method('delete')
                                <button class="icon-btn" type="submit" aria-label="Quitar {{ $line->product->name }}">@include('partials.icon', ['name' => 'x'])</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <aside class="card stack-lg">
                <div class="totals">
                    <div class="totals-row grand"><span>Total</span><span class="num">{{ money($total) }}</span></div>
                </div>
                <a class="btn btn-primary btn-block" href="{{ route('checkout') }}">Finalizar pedido</a>
                <a class="btn btn-quiet btn-block" href="{{ route('catalog') }}">Seguir comprando</a>
            </aside>
        </div>
    @endif
@endsection
