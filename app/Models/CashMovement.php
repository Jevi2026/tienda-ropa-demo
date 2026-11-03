<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashMovement extends Model
{
    public const LABELS = [
        'ingreso' => 'Ingreso',
        'gasto' => 'Gasto',
        'retiro' => 'Retiro',
    ];

    protected $fillable = ['type', 'concept', 'amount', 'moved_at', 'user_id'];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'moved_at' => 'datetime',
        ];
    }

    /** Monto con signo según entre o salga dinero de la caja. */
    public function signedAmount(): int
    {
        return $this->type === 'ingreso' ? $this->amount : -$this->amount;
    }
}
