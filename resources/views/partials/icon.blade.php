@php
    $paths = [
        'home' => '<path d="m3 11 9-8 9 8"/><path d="M5 9.5V21h14V9.5"/><path d="M10 21v-6h4v6"/>',
        'shirt' => '<path d="M8 3 3 6l2 5 3-1v11h8V10l3 1 2-5-5-3c-.5 1.5-2 2.5-4 2.5S8.500 4.500 8 3Z"/>',
        'bag' => '<path d="M5 8h14l1 13H4L5 8Z"/><path d="M9 11V6a3 3 0 0 1 6 0v5"/>',
        'receipt' => '<path d="M5 3v18l2.500-1.500L10 21l2-1.500 2 1.500 2.500-1.500L19 21V3l-2.500 1.500L14 3l-2 1.500L10 3 7.500 4.500 5 3Z"/><path d="M9 9h6M9 13h6"/>',
        'cash' => '<rect x="2.500" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.500"/><path d="M6 10v4M18 10v4"/>',
        'store' => '<path d="M4 9.500 5.500 4h13L20 9.500"/><path d="M4 9.500a2.700 2.700 0 0 0 5.300 0 2.700 2.700 0 0 0 5.400 0 2.700 2.700 0 0 0 5.300 0"/><path d="M5 12v9h14v-9"/>',
        'logout' => '<path d="M10 4H5v16h5"/><path d="m15 8 4 4-4 4"/><path d="M19 12H10"/>',
        'arrow' => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
        'chevron' => '<path d="m9 6 6 6-6 6"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'x' => '<path d="m6 6 12 12M18 6 6 18"/>',
        'print' => '<path d="M7 8V3h10v5"/><rect x="3" y="8" width="18" height="9" rx="2"/><path d="M7 14h10v7H7z"/>',
        'chat' => '<path d="M4 20l1.500-4A8 8 0 1 1 9 19l-5 1Z"/>',
    ];
@endphp
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths[$name] !!}</svg>
