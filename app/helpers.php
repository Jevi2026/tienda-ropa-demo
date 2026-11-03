<?php

if (! function_exists('money')) {
    /** Formatea centavos para mostrarlos: 125000 => "L 1,250.00". */
    function money(int|string|null $cents): string
    {
        $cents = (int) $cents;
        $decimals = (int) config('tienda.decimales');
        $amount = number_format(abs($cents) / 100, $decimals, '.', ',');

        return ($cents < 0 ? '-' : '').config('tienda.moneda').' '.$amount;
    }
}

if (! function_exists('to_cents')) {
    /** Convierte lo escrito en un formulario a centavos: "1250.5" => 125050. */
    function to_cents(int|float|string|null $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}

if (! function_exists('from_cents')) {
    /** Valor para precargar un input numérico: 125050 => "1250.50". */
    function from_cents(int|string|null $cents): string
    {
        return number_format(((int) $cents) / 100, 2, '.', '');
    }
}

if (! function_exists('whatsapp_url')) {
    /** Enlace a un chat de WhatsApp, o null si no hay un número utilizable. */
    function whatsapp_url(?string $phone, string $text = ''): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) <= 8) {
            $digits = config('tienda.codigo_pais').$digits;
        }

        return 'https://wa.me/'.$digits.($text !== '' ? '?text='.rawurlencode($text) : '');
    }
}
