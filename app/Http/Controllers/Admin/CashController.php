<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashClosing;
use App\Models\CashMovement;
use App\Services\CashRegister;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CashController extends Controller
{
    public function index(Request $request, CashRegister $cash): View
    {
        $today = now()->toDateString();
        $day = (string) $request->query('dia', $today);

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $day) || ! strtotime($day) || $day > $today) {
            $day = $today;
        }

        $closing = CashClosing::with('closer')->where('day', $day)->first();
        $opening = $closing?->opening_amount ?? $cash->defaultOpening($day);
        $summary = $cash->summary($day);

        return view('admin.cash.index', [
            'day' => $day,
            'isToday' => $day === $today,
            'closing' => $closing,
            'opening' => $opening,
            'summary' => $summary,
            'expected' => $closing?->expected_cash ?? $cash->expectedCash($summary, $opening),
            'canClose' => ! $closing && ! CashClosing::where('day', '>', $day)->exists(),
            'canReopen' => $closing && ! CashClosing::where('day', '>', $day)->exists(),
            'history' => CashClosing::orderByDesc('day')->take(10)->get(),
        ]);
    }

    public function close(Request $request, CashRegister $cash): RedirectResponse
    {
        $data = $request->validate([
            'day' => ['required', 'date_format:Y-m-d'],
            'opening_amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'counted_cash' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $closing = $cash->close(
            $data['day'],
            to_cents($data['opening_amount']),
            to_cents($data['counted_cash']),
            $data['notes'] ?? null,
            $request->user()->id,
        );

        return redirect()->route('admin.cash.index', ['dia' => $closing->day])->with('status', 'Caja cerrada.');
    }

    public function reopen(CashClosing $closing, CashRegister $cash): RedirectResponse
    {
        $day = $closing->day;

        $cash->reopen($closing);

        return redirect()->route('admin.cash.index', ['dia' => $day])->with('status', 'Caja reabierta. Ese cierre se eliminó.');
    }

    public function movement(Request $request, CashRegister $cash): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(CashMovement::LABELS))],
            'concept' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
        ]);

        $cash->addMovement($data['type'], $data['concept'], to_cents($data['amount']), $request->user()->id);

        return back()->with('status', 'Movimiento registrado.');
    }
}
