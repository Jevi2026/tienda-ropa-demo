@extends('layouts.store')

@section('title', $product->name)
@section('description', \Illuminate\Support\Str::limit((string) $product->description, 150))

@section('content')
    <div class="product-layout">
        <div class="product-photo">
            <div class="photo">
                @if ($product->imageSrc())
                    <img src="{{ $product->imageSrc() }}" alt="{{ $product->name }}">
                @endif
            </div>
        </div>

        <div class="stack-lg">
            <div class="stack">
                <a class="small muted" href="{{ route('catalog', ['categoria' => $product->category->slug]) }}">{{ $product->category->name }}</a>
                <h1>{{ $product->name }}</h1>
                <p class="num" style="font-size: 20px">{{ money($product->price) }}</p>
                @if ($product->description)
                    <p class="muted">{{ $product->description }}</p>
                @endif
            </div>

            <form class="stack-lg" method="post" action="{{ route('cart.add') }}">
                @csrf
                <fieldset style="border: 0; margin: 0; padding: 0" class="stack">
                    <legend class="label" style="padding: 0; margin-bottom: 10px">Elige tu talla</legend>
                    <div class="size-options">
                        @foreach ($product->variants as $variant)
                            <label class="size-option">
                                <input type="radio" name="variant_id" value="{{ $variant->id }}" required
                                    @disabled($variant->stock < 1)
                                    @checked(old('variant_id') == $variant->id)>
                                <span>{{ $variant->size }}<span class="sr-only">@if ($variant->stock < 1) (agotada) @endif</span></span>
                            </label>
                        @endforeach
                    </div>
                    @php $few = $product->variants->filter(fn ($v) => $v->stock > 0 && $v->stock <= $product->low_stock_at); @endphp
                    @if ($few->isNotEmpty())
                        <p class="hint">Últimas unidades en talla {{ $few->pluck('size')->join(', ', ' y ') }}.</p>
                    @endif
                </fieldset>

                @if ($product->stockTotal() > 0)
                    <button class="btn btn-primary btn-block" type="submit" data-submit-once>Agregar al carrito</button>
                @else
                    <p class="semibold">Esta prenda está agotada por ahora.</p>
                @endif
            </form>
        </div>
    </div>

    @if ($related->isNotEmpty())
        <section class="section">
            <div class="section-head"><h2>También te puede gustar</h2></div>
            <div class="product-grid">
                @foreach ($related as $product)
                    @include('partials.product-card')
                @endforeach
            </div>
        </section>
    @endif
@endsection
