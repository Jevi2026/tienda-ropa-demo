<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUS_LABELS = [
        'pendiente' => 'Pendiente',
        'parcial' => 'Abonado',
        'pagado' => 'Pagado',
        'cancelado' => 'Cancelado',
    ];

    /** Nombre que se usa en ventas de mostrador cuando no se pide el nombre. */
    public const WALK_IN_NAME = 'Cliente de mostrador';

    protected $fillable = [
        'number', 'public_token', 'channel', 'customer_name', 'customer_phone',
        'delivery_method', 'address', 'payment_preference', 'notes', 'total',
        'amount_paid', 'delivered_at', 'cancelled_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'integer',
            'amount_paid' => 'integer',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('paid_at');
    }

    public function scopeWithStatus(Builder $query, ?string $status): Builder
    {
        return match ($status) {
            'pendiente' => $query->whereNull('cancelled_at')->where('amount_paid', 0),
            'parcial' => $query->whereNull('cancelled_at')->where('amount_paid', '>', 0)->whereColumn('amount_paid', '<', 'total'),
            'pagado' => $query->whereNull('cancelled_at')->whereColumn('amount_paid', '>=', 'total'),
            'cancelado' => $query->whereNotNull('cancelled_at'),
            'por_cobrar' => $query->whereNull('cancelled_at')->whereColumn('amount_paid', '<', 'total'),
            default => $query,
        };
    }

    public function status(): string
    {
        return match (true) {
            $this->cancelled_at !== null => 'cancelado',
            $this->amount_paid >= $this->total => 'pagado',
            $this->amount_paid > 0 => 'parcial',
            default => 'pendiente',
        };
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status()];
    }

    public function balance(): int
    {
        return $this->cancelled_at ? 0 : max(0, $this->total - $this->amount_paid);
    }

    /**
     * Identifica a la persona para contar compradores distintos en el día.
     * Cada venta de mostrador sin datos cuenta como una persona.
     */
    public function customerKey(): string
    {
        $phone = preg_replace('/\D+/', '', (string) $this->customer_phone);

        if ($phone !== '') {
            return 'tel:'.$phone;
        }

        if ($this->customer_name === self::WALK_IN_NAME) {
            return 'pedido:'.$this->id;
        }

        return 'nombre:'.mb_strtolower(trim($this->customer_name));
    }
}
