@extends('layouts.store')

@section('title', 'Finalizar pedido')
@section('noindex', '1')

@section('content')
    <div class="page-head"><h1>Finalizar pedido</h1></div>

    <form method="post" action="{{ route('checkout.store') }}" class="split">
        @csrf
        <div class="card stack-lg">
            <div class="form-grid">
                <div class="field">
                    <label for="customer_name">Nombre completo</label>
                    <input class="input" id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required maxlength="120" autocomplete="name">
                </div>
                <div class="field">
                    <label for="customer_phone">Teléfono o WhatsApp</label>
                    <input class="input" id="customer_phone" name="customer_phone" type="tel" value="{{ old('customer_phone') }}" required maxlength="40" autocomplete="tel" placeholder="9999-9999">
                </div>
                <div class="field" data-delivery>
                    <label for="delivery_method">Entrega</label>
                    <select class="select" id="delivery_method" name="delivery_method">
                        <option value="retiro" @selected(old('delivery_method') === 'retiro')>Retirar en tienda</option>
                        <option value="envio" @selected(old('delivery_method') === 'envio')>Envío a domicilio</option>
                    </select>
                </div>
                <div class="field">
                    <label for="payment_preference">¿Cómo vas a pagar?</label>
                    <select class="select" id="payment_preference" name="payment_preference">
                        @foreach (config('tienda.metodos_pago') as $key => $label)
                            <option value="{{ $key }}" @selected(old('payment_preference') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field span-2" data-address>
                    <label for="address">Dirección de envío</label>
                    <input class="input" id="address" name="address" value="{{ old('address') }}" maxlength="255" autocomplete="street-address">
                </div>
                <div class="field span-2">
                    <label for="notes">Notas (opcional)</label>
                    <textarea class="textarea" id="notes" name="notes" maxlength="500">{{ old('notes') }}</textarea>
                </div>
            </div>
            <p class="hint">No cobramos en línea: la tienda te contacta para confirmar el pago y la entrega.</p>
        </div>

        <aside class="card stack-lg">
            <h2>Tu pedido</h2>
            <div>
                @foreach ($lines as $line)
                    <div class="list-row">
                        <span>{{ $line->quantity }} × {{ $line->product->name }} <span class="muted">({{ $line->variant->size }})</span></span>
                        <span class="num nowrap">{{ money($line->total) }}</span>
                    </div>
                @endforeach
            </div>
            <div class="totals-row grand totals"><span>Total</span><span class="num">{{ money($total) }}</span></div>
            <button class="btn btn-primary btn-block" type="submit" data-submit-once>Confirmar pedido</button>
        </aside>
    </form>
@endsection
