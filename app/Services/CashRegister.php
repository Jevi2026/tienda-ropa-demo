<?php

namespace App\Services;

use App\Models\CashClosing;
use App\Models\CashMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Corte de caja diario. El dinero se cuenta el día en que entra: un pedido del
 * lunes que se paga el martes suma a la caja del martes.
 */
class CashRegister
{
    /** @return array<string, mixed> */
    public function summary(string $day): array
    {
        $from = CarbonImmutable::parse($day)->startOfDay();
        $to = $from->endOfDay();

        $payments = Payment::with('order')
            ->whereBetween('paid_at', [$from, $to])
            ->orderBy('paid_at')
            ->get();

        $byMethod = array_map(fn () => 0, config('tienda.metodos_pago'));

        foreach ($payments as $payment) {
            $byMethod[$payment->method] = ($byMethod[$payment->method] ?? 0) + $payment->amount;
        }

        $movements = CashMovement::whereBetween('moved_at', [$from, $to])->orderBy('moved_at')->get();
        $cashIn = (int) $movements->where('type', 'ingreso')->sum('amount');
        $cashOut = (int) $movements->where('type', '!=', 'ingreso')->sum('amount');

        $orders = Order::with('items')->whereBetween('created_at', [$from, $to])->orderByDesc('created_at')->get();
        $active = $orders->whereNull('cancelled_at');
        $items = $active->flatMap->items;

        $pending = Order::withStatus('por_cobrar')
            ->where('created_at', '<=', $to)
            ->sum(DB::raw('total - amount_paid'));

        return [
            'day' => $day,
            'payments' => $payments,
            'movements' => $movements,
            'orders' => $orders,
            'payments_by_method' => $byMethod,
            'cash_sales' => $byMethod[config('tienda.metodo_efectivo')] ?? 0,
            'collected_total' => array_sum($byMethod),
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'customers_count' => $active->map->customerKey()->unique()->count(),
            'orders_count' => $active->count(),
            'orders_paid_count' => $active->filter(fn (Order $o) => $o->status() === 'pagado')->count(),
            'orders_pending_count' => $active->filter(fn (Order $o) => $o->status() !== 'pagado')->count(),
            'items_count' => (int) $items->sum('quantity'),
            'sales_total' => (int) $active->sum('total'),
            'gross_profit' => (int) $items->sum(fn (OrderItem $i) => $i->line_total - $i->unit_cost * $i->quantity),
            'pending_balance' => (int) $pending,
        ];
    }

    /** Lo que debería haber en efectivo: apertura + cobros en efectivo + ingresos - salidas. */
    public function expectedCash(array $summary, int $opening): int
    {
        return $opening + $summary['cash_sales'] + $summary['cash_in'] - $summary['cash_out'];
    }

    /** La caja arranca con lo que se contó en el cierre anterior. */
    public function defaultOpening(string $day): int
    {
        return (int) CashClosing::where('day', '<', $day)->orderByDesc('day')->value('counted_cash');
    }

    public function close(string $day, int $opening, int $counted, ?string $notes, ?int $userId): CashClosing
    {
        return DB::transaction(function () use ($day, $opening, $counted, $notes, $userId) {
            if ($day > now()->toDateString()) {
                throw ValidationException::withMessages(['day' => 'No se puede cerrar un día que aún no ocurre.']);
            }

            if (CashClosing::where('day', $day)->exists()) {
                throw ValidationException::withMessages(['day' => 'Este día ya tiene un cierre de caja.']);
            }

            if (CashClosing::where('day', '>', $day)->exists()) {
                throw ValidationException::withMessages(['day' => 'Ya existe un cierre posterior a este día.']);
            }

            $summary = $this->summary($day);
            $expected = $this->expectedCash($summary, $opening);

            return CashClosing::create([
                'day' => $day,
                'opening_amount' => $opening,
                'payments_by_method' => $summary['payments_by_method'],
                'collected_total' => $summary['collected_total'],
                'cash_in' => $summary['cash_in'],
                'cash_out' => $summary['cash_out'],
                'expected_cash' => $expected,
                'counted_cash' => $counted,
                'difference' => $counted - $expected,
                'customers_count' => $summary['customers_count'],
                'orders_count' => $summary['orders_count'],
                'orders_paid_count' => $summary['orders_paid_count'],
                'orders_pending_count' => $summary['orders_pending_count'],
                'items_count' => $summary['items_count'],
                'sales_total' => $summary['sales_total'],
                'gross_profit' => $summary['gross_profit'],
                'pending_balance' => $summary['pending_balance'],
                'notes' => $notes,
                'closed_by' => $userId,
                'closed_at' => now(),
            ]);
        });
    }

    /** Solo el último cierre se puede reabrir: los anteriores ya alimentan al siguiente. */
    public function reopen(CashClosing $closing): void
    {
        if (CashClosing::where('day', '>', $closing->day)->exists()) {
            throw ValidationException::withMessages(['day' => 'Solo se puede reabrir el cierre más reciente.']);
        }

        $closing->delete();
    }

    public function addMovement(string $type, string $concept, int $amount, ?int $userId): CashMovement
    {
        if (CashClosing::where('day', now()->toDateString())->exists()) {
            throw ValidationException::withMessages(['amount' => 'La caja de hoy ya está cerrada. Reábrela para registrar movimientos.']);
        }

        return CashMovement::create([
            'type' => $type,
            'concept' => $concept,
            'amount' => $amount,
            'moved_at' => now(),
            'user_id' => $userId,
        ]);
    }
}
