@extends('layouts.admin')

@section('title', 'Nueva venta')

@section('content')
    @php
        $rows = old('items', [['variant_id' => '', 'quantity' => 1]]);
        $options = function ($selected = null) use ($products) {
            $html = '<option value="">Elegir prenda</option>';
            foreach ($products as $product) {
                foreach ($product->variants as $variant) {
                    $html .= '<option value="'.$variant->id.'" data-price="'.$product->price.'" data-stock="'.$variant->stock.'"'
                        .((string) $selected === (string) $variant->id ? ' selected' : '').'>'
                        .e($product->name.' · '.$variant->size.' · '.money($product->price).' · quedan '.$variant->stock)
                        .'</option>';
                }
            }

            return $html;
        };
    @endphp

    <div class="page-head">
        <div>
            <a class="small muted" href="{{ route('admin.orders.index') }}">Pedidos</a>
            <h1>Nueva venta</h1>
        </div>
    </div>

    <form method="post" action="{{ route('admin.orders.store') }}" class="split" data-sale-form>
        @csrf
        <div class="stack-lg">
            <section class="card" data-repeater data-next="{{ count($rows) }}">
                <div class="section-head" style="margin-bottom: 16px">
                    <h2>Prendas</h2>
                    <button class="btn" type="button" data-repeater-add>@include('partials.icon', ['name' => 'plus']) Otra prenda</button>
                </div>
                <div class="stack" data-repeater-list>
                    @foreach ($rows as $i => $row)
                        <div class="sale-row" data-repeater-row>
                            <div>
                                <label class="sr-only" for="variant-{{ $i }}">Prenda</label>
                                <select class="select" id="variant-{{ $i }}" name="items[{{ $i }}][variant_id]" required>{!! $options($row['variant_id'] ?? null) !!}</select>
                            </div>
                            <div>
                                <label class="sr-only" for="quantity-{{ $i }}">Cantidad</label>
                                <input class="input num" id="quantity-{{ $i }}" name="items[{{ $i }}][quantity]" type="number" min="1" value="{{ $row['quantity'] ?? 1 }}" required>
                            </div>
                            <button class="icon-btn" type="button" data-repeater-remove aria-label="Quitar prenda">@include('partials.icon', ['name' => 'x'])</button>
                        </div>
                    @endforeach
                </div>
                <template>
                    <div class="sale-row" data-repeater-row>
                        <div>
                            <label class="sr-only" for="variant-__i__">Prenda</label>
                            <select class="select" id="variant-__i__" name="items[__i__][variant_id]" required>{!! $options() !!}</select>
                        </div>
                        <div>
                            <label class="sr-only" for="quantity-__i__">Cantidad</label>
                            <input class="input num" id="quantity-__i__" name="items[__i__][quantity]" type="number" min="1" value="1" required>
                        </div>
                        <button class="icon-btn" type="button" data-repeater-remove aria-label="Quitar prenda">@include('partials.icon', ['name' => 'x'])</button>
                    </div>
                </template>
            </section>

            <section class="card">
                <h2 class="card-title">Cliente <span class="muted small" style="font-weight: 400">(opcional en mostrador)</span></h2>
                <div class="form-grid">
                    <div class="field">
                        <label for="customer_name">Nombre</label>
                        <input class="input" id="customer_name" name="customer_name" value="{{ old('customer_name') }}" maxlength="120">
                    </div>
                    <div class="field">
                        <label for="customer_phone">Teléfono</label>
                        <input class="input" id="customer_phone" name="customer_phone" type="tel" value="{{ old('customer_phone') }}" maxlength="40">
                    </div>
                    <div class="field" data-delivery>
                        <label for="delivery_method">Entrega</label>
                        <select class="select" id="delivery_method" name="delivery_method">
                            <option value="retiro" @selected(old('delivery_method') === 'retiro')>Se lleva en tienda</option>
                            <option value="envio" @selected(old('delivery_method') === 'envio')>Envío a domicilio</option>
                        </select>
                    </div>
                    <div class="field" data-address>
                        <label for="address">Dirección de envío</label>
                        <input class="input" id="address" name="address" value="{{ old('address') }}" maxlength="255">
                    </div>
                    <div class="field span-2">
                        <label for="notes">Notas</label>
                        <input class="input" id="notes" name="notes" value="{{ old('notes') }}" maxlength="500">
                    </div>
                </div>
            </section>
        </div>

        <aside class="card stack-lg">
            <div class="totals-row grand totals" style="border: 0; padding: 0"><span>Total</span><span class="num" data-sale-total>{{ money(0) }}</span></div>
            <div class="stack">
                <div class="field">
                    <label for="payment_amount">Cobrado ahora ({{ config('tienda.moneda') }})</label>
                    <input class="input num" id="payment_amount" name="payment_amount" type="number" step="0.01" min="0" value="{{ old('payment_amount') }}" data-sale-amount>
                    <span class="hint">Déjalo en 0 o vacío si queda pendiente, o escribe un abono.</span>
                </div>
                <div class="field">
                    <label for="payment_method">Método</label>
                    <select class="select" id="payment_method" name="payment_method">
                        @foreach (config('tienda.metodos_pago') as $key => $label)
                            <option value="{{ $key }}" @selected(old('payment_method') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button class="btn btn-primary btn-block" type="submit" data-submit-once>Registrar venta</button>
        </aside>
    </form>
@endsection
