<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashClosing extends Model
{
    protected $fillable = [
        'day', 'opening_amount', 'payments_by_method', 'collected_total',
        'cash_in', 'cash_out', 'expected_cash', 'counted_cash', 'difference',
        'customers_count', 'orders_count', 'orders_paid_count',
        'orders_pending_count', 'items_count', 'sales_total', 'gross_profit',
        'pending_balance', 'notes', 'closed_by', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'payments_by_method' => 'array',
            'opening_amount' => 'integer',
            'collected_total' => 'integer',
            'cash_in' => 'integer',
            'cash_out' => 'integer',
            'expected_cash' => 'integer',
            'counted_cash' => 'integer',
            'difference' => 'integer',
            'sales_total' => 'integer',
            'gross_profit' => 'integer',
            'pending_balance' => 'integer',
            'closed_at' => 'datetime',
        ];
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
