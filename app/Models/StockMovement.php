<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const LABELS = [
        'entrada' => 'Entrada',
        'ajuste' => 'Ajuste',
        'venta' => 'Venta',
        'devolucion' => 'Devolución',
    ];

    protected $fillable = ['product_variant_id', 'order_id', 'user_id', 'type', 'quantity', 'note'];

    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
