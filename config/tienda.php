<?php

/*
 | Lo que cambia de un cliente a otro. Para adaptar la demo a una tienda real
 | casi todo se ajusta desde el archivo .env.
 */
return [
    'nombre' => env('APP_NAME', 'Hilo'),

    // Muestra la banda "tienda de demostración" y el acceso de prueba al panel.
    'demo' => (bool) env('TIENDA_DEMO', true),

    'moneda' => env('TIENDA_MONEDA', 'L'),
    'decimales' => (int) env('TIENDA_DECIMALES', 2),

    // Número de la tienda con código de país y sin signos. Vacío oculta el botón.
    'whatsapp' => preg_replace('/\D+/', '', (string) env('TIENDA_WHATSAPP', '')),

    // Se antepone a los teléfonos de clientes que se escriben sin código de país.
    'codigo_pais' => env('TIENDA_CODIGO_PAIS', '504'),

    'metodos_pago' => [
        'efectivo' => 'Efectivo',
        'transferencia' => 'Transferencia',
        'tarjeta' => 'Tarjeta',
        'tigo_money' => 'Tigo Money',
    ],

    // Clave del método que entra físicamente a la caja.
    'metodo_efectivo' => 'efectivo',
];
