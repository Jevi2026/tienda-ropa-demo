<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Services\CashRegister;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(CashRegister $cash): View
    {
        $today = CarbonImmutable::today();
        $from = $today->subDays(6)->startOfDay();

        $collected = Payment::where('paid_at', '>=', $from)->get(['amount', 'paid_at'])
            ->groupBy(fn (Payment $p) => $p->paid_at->toDateString())
            ->map(fn ($group) => (int) $group->sum('amount'));

        $days = collect(range(6, 0))->map(function (int $back) use ($today, $collected) {
            $date = $today->subDays($back);

            return (object) [
                'date' => $date,
                'label' => ucfirst($date->locale('es')->isoFormat('ddd')),
                'long' => ucfirst($date->locale('es')->isoFormat('dddd D [de] MMMM')),
                'amount' => max(0, $collected[$date->toDateString()] ?? 0),
                'is_today' => $back === 0,
            ];
        });

        $lowStock = Product::active()->lowStock()->with('variants')->orderBy('name')->take(6)->get();

        return view('admin.dashboard', [
            'summary' => $cash->summary($today->toDateString()),
            'days' => $days,
            'chartMax' => $this->niceMax((int) $days->max('amount')),
            'toCollect' => Order::withStatus('por_cobrar')->with('items')->oldest()->take(6)->get(),
            'toCollectCount' => Order::withStatus('por_cobrar')->count(),
            'lowStock' => $lowStock,
            'lowStockCount' => Product::active()->lowStock()->count(),
        ]);
    }

    /** Redondea el tope del eje a un número fácil de leer. */
    private function niceMax(int $max): int
    {
        if ($max <= 0) {
            return 100000;
        }

        $magnitude = 10 ** floor(log10($max));

        foreach ([1, 2, 2.5, 5, 10] as $step) {
            if ($step * $magnitude >= $max) {
                return (int) ($step * $magnitude);
            }
        }

        return $max;
    }
}
