<a class="product-card" href="{{ route('product', $product) }}">
    <div class="photo">
        @if ($product->imageSrc())
            <img src="{{ $product->imageSrc() }}" alt="{{ $product->name }}" loading="lazy">
        @endif
        @if ($product->stockTotal() === 0)
            <span class="photo-tag">Agotado</span>
        @endif
    </div>
    <div class="product-card-body">
        <span class="product-card-name">{{ $product->name }}</span>
        <span class="product-card-price num">{{ money($product->price) }}</span>
    </div>
</a>
