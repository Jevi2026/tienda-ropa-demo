@extends('layouts.admin')

@section('title', 'Caja')

@section('content')
    @php
        // Si el día está cerrado se muestra la foto guardada, no el cálculo de ahora.
        $data = $closing ?? (object) $summary;
        $byMethod = $closing ? $closing->payments_by_method : $summary['payments_by_method'];
        $methods = config('tienda.metodos_pago');
        $cashKey = config('tienda.metodo_efectivo');
        $dayLabel = ucfirst(\Carbon\Carbon::parse($day)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY'));
    @endphp

    <div class="page-head">
        <div>
            <h1>{{ $isToday ? 'Caja de hoy' : 'Caja del día' }}</h1>
            <p class="small muted">{{ $dayLabel }}</p>
        </div>
        <div class="row no-print">
            <form class="row" style="gap: 8px">
                <label class="sr-only" for="dia">Ver otro día</label>
                <input class="input" id="dia" type="date" name="dia" value="{{ $day }}" max="{{ now()->toDateString() }}" onchange="this.form.submit()" style="width: auto">
                <noscript><button class="btn" type="submit">Ver</button></noscript>
            </form>
            <button class="btn" type="button" onclick="window.print()">@include('partials.icon', ['name' => 'print']) Imprimir</button>
        </div>
    </div>

    <div class="kpis">
        <div class="kpi">
            <span class="kpi-label">Personas que compraron</span>
            <span class="kpi-value">{{ $data->customers_count }}</span>
            <span class="kpi-note">{{ $data->items_count }} prendas vendidas</span>
        </div>
        <div class="kpi">
            <span class="kpi-label">Pedidos del día</span>
            <span class="kpi-value">{{ $data->orders_count }}</span>
            <span class="kpi-note">{{ $data->orders_paid_count }} pagados · {{ $data->orders_pending_count }} por cobrar</span>
        </div>
        <div class="kpi">
            <span class="kpi-label">Cobrado en el día</span>
            <span class="kpi-value">{{ money($data->collected_total) }}</span>
            <span class="kpi-note">Ventas del día: {{ money($data->sales_total) }}</span>
        </div>
        <div class="kpi">
            <span class="kpi-label">Ganancia estimada</span>
            <span class="kpi-value">{{ money($data->gross_profit) }}</span>
            <span class="kpi-note">Ventas del día menos costo</span>
        </div>
    </div>

    <div class="panel-grid">
        <div class="stack-lg">
            <section class="card">
                <h2 class="card-title">Cobros por método</h2>
                @foreach ($byMethod as $key => $amount)
                    <div class="cash-line"><span>{{ $methods[$key] ?? $key }}</span><span class="num">{{ money($amount) }}</span></div>
                @endforeach
                <div class="cash-line total"><span>Total cobrado</span><span class="num">{{ money($data->collected_total) }}</span></div>
                <p class="hint" style="margin-top: 8px">Por cobrar, sumando todos los días: {{ money($data->pending_balance) }}</p>
            </section>

            <section class="card card-flush">
                <h2 style="padding: 24px 24px 12px">Pagos recibidos</h2>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Hora</th><th>Pedido</th><th class="hide-sm">Cliente</th><th>Método</th><th class="right">Monto</th></tr></thead>
                        <tbody>
                            @forelse ($summary['payments'] as $payment)
                                <tr>
                                    <td class="nowrap">{{ $payment->paid_at->format('g:i a') }}</td>
                                    <td><a class="strong" href="{{ route('admin.orders.show', $payment->order) }}">{{ $payment->order->number }}</a></td>
                                    <td class="hide-sm">{{ $payment->order->customer_name }}</td>
                                    <td>{{ $payment->methodLabel() }}</td>
                                    <td class="right num nowrap">{{ money($payment->amount) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="muted">No se recibieron pagos este día.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card card-flush">
                <h2 style="padding: 24px 24px 12px">Pedidos del día</h2>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Pedido</th><th>Cliente</th><th>Estado</th><th class="right">Total</th></tr></thead>
                        <tbody>
                            @forelse ($summary['orders'] as $order)
                                <tr>
                                    <td><a class="strong" href="{{ route('admin.orders.show', $order) }}">{{ $order->number }}</a></td>
                                    <td>{{ $order->customer_name }}</td>
                                    <td>@include('partials.order-status')</td>
                                    <td class="right num nowrap">{{ money($order->total) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="muted">No hubo pedidos este día.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="stack-lg">
            <section class="card">
                <h2 class="card-title">Efectivo en caja</h2>
                <div class="cash-line"><span>Apertura</span><span class="num">{{ money($opening) }}</span></div>
                <div class="cash-line"><span>Cobros en efectivo</span><span class="num">{{ money($byMethod[$cashKey] ?? 0) }}</span></div>
                <div class="cash-line"><span>Otros ingresos</span><span class="num">{{ money($data->cash_in) }}</span></div>
                <div class="cash-line"><span>Gastos y retiros</span><span class="num">{{ money(-$data->cash_out) }}</span></div>
                <div class="cash-line total"><span>Debería haber</span><span class="num" data-expected>{{ money($expected) }}</span></div>

                @if ($closing)
                    <div class="cash-line"><span>Se contó</span><span class="num">{{ money($closing->counted_cash) }}</span></div>
                    <div class="difference semibold small" style="margin-top: 8px">
                        @if ($closing->difference === 0)
                            La caja cuadró exacto.
                        @else
                            {{ $closing->difference > 0 ? 'Sobrante' : 'Faltante' }} de {{ money(abs($closing->difference)) }}
                        @endif
                    </div>
                    @if ($closing->notes)
                        <p class="small" style="margin-top: 12px">“{{ $closing->notes }}”</p>
                    @endif
                    <p class="hint" style="margin-top: 12px">Cerrada el {{ $closing->closed_at->locale('es')->isoFormat('D MMM, h:mm a') }}{{ $closing->closer ? ' por '.$closing->closer->name : '' }}.</p>
                    @if ($canReopen)
                        <form class="no-print" method="post" action="{{ route('admin.cash.reopen', $closing) }}" data-confirm="¿Reabrir la caja? Se elimina este cierre y podrás volver a hacerlo." style="margin-top: 8px">
                            @csrf
                            <button class="btn btn-quiet" type="submit">Reabrir caja</button>
                        </form>
                    @endif
                @elseif ($canClose)
                    <form class="stack no-print" method="post" action="{{ route('admin.cash.close') }}" data-cash-form
                          data-base="{{ $expected - $opening }}"
                          data-confirm="¿Cerrar la caja de este día? Después ya no se podrán registrar pagos hasta reabrirla."
                          style="margin-top: 16px">
                        @csrf
                        <input type="hidden" name="day" value="{{ $day }}">
                        <div class="field">
                            <label for="opening_amount">Apertura ({{ config('tienda.moneda') }})</label>
                            <input class="input num" id="opening_amount" name="opening_amount" type="number" step="0.01" min="0" value="{{ old('opening_amount', from_cents($opening)) }}" required>
                        </div>
                        <div class="field">
                            <label for="counted_cash">Efectivo contado ({{ config('tienda.moneda') }})</label>
                            <input class="input num" id="counted_cash" name="counted_cash" type="number" step="0.01" min="0" value="{{ old('counted_cash') }}" required>
                        </div>
                        <div class="difference semibold small" data-difference aria-live="polite"></div>
                        <div class="field">
                            <label for="notes">Notas</label>
                            <input class="input" id="notes" name="notes" value="{{ old('notes') }}" maxlength="500">
                        </div>
                        <button class="btn btn-primary btn-block" type="submit" data-submit-once>Cerrar caja</button>
                    </form>
                @else
                    <p class="hint" style="margin-top: 12px">Este día no se cerró y ya hay un cierre posterior.</p>
                @endif
            </section>

            <section class="card">
                <h2 class="card-title">Gastos y otros movimientos</h2>
                @forelse ($summary['movements'] as $movement)
                    <div class="list-row">
                        <span><span class="list-row-title">{{ $movement->concept }}</span> <span class="muted">· {{ \App\Models\CashMovement::LABELS[$movement->type] }}</span></span>
                        <span class="num nowrap">{{ money($movement->signedAmount()) }}</span>
                    </div>
                @empty
                    <p class="small muted">Sin movimientos este día.</p>
                @endforelse

                @if ($isToday && ! $closing)
                    <form class="stack no-print" method="post" action="{{ route('admin.cash.movement') }}" style="margin-top: 16px">
                        @csrf
                        <div class="form-grid">
                            <div class="field">
                                <label for="type">Tipo</label>
                                <select class="select" id="type" name="type">
                                    @foreach (\App\Models\CashMovement::LABELS as $key => $label)
                                        <option value="{{ $key }}" @selected(old('type', 'gasto') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label for="amount">Monto ({{ config('tienda.moneda') }})</label>
                                <input class="input num" id="amount" name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount') }}" required>
                            </div>
                            <div class="field span-2">
                                <label for="concept">Concepto</label>
                                <input class="input" id="concept" name="concept" value="{{ old('concept') }}" maxlength="120" required placeholder="Pago de transporte">
                            </div>
                        </div>
                        <button class="btn btn-block" type="submit" data-submit-once>Agregar movimiento</button>
                    </form>
                @endif
            </section>

            <section class="card no-print">
                <h2 class="card-title">Cierres anteriores</h2>
                @forelse ($history as $item)
                    <a class="list-row" href="{{ route('admin.cash.index', ['dia' => $item->day]) }}">
                        <span class="list-row-title">{{ ucfirst(\Carbon\Carbon::parse($item->day)->locale('es')->isoFormat('ddd D MMM')) }}</span>
                        <span class="num nowrap">
                            {{ money($item->collected_total) }}
                            @if ($item->difference !== 0)
                                <span class="tag tag-low" style="margin-left: 4px">{{ $item->difference > 0 ? 'Sobrante' : 'Faltante' }}</span>
                            @endif
                        </span>
                    </a>
                @empty
                    <p class="small muted">Aún no hay cierres.</p>
                @endforelse
            </section>
        </div>
    </div>
@endsection
