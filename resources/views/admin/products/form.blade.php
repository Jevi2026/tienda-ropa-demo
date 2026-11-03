@extends('layouts.admin')

@section('title', $product->exists ? $product->name : 'Nueva prenda')

@section('content')
    @php
        $rows = old('variants', $product->exists
            ? $product->variants->map(fn ($v) => ['id' => $v->id, 'size' => $v->size, 'stock' => $v->stock, 'original_stock' => $v->stock])->all()
            : [['id' => null, 'size' => '', 'stock' => 0, 'original_stock' => null]]);
    @endphp

    <div class="page-head">
        <div>
            <a class="small muted" href="{{ route('admin.products.index') }}">Inventario</a>
            <h1>{{ $product->exists ? $product->name : 'Nueva prenda' }}</h1>
        </div>
        @if ($product->exists && $product->is_active)
            <a class="btn" href="{{ route('product', $product) }}" target="_blank" rel="noopener">Ver en la tienda</a>
        @endif
    </div>

    <form method="post" enctype="multipart/form-data"
          action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}">
        @csrf
        @if ($product->exists)
            @method('put')
        @endif

        <div class="split">
            <div class="stack-lg">
                <section class="card">
                    <h2 class="card-title">Datos de la prenda</h2>
                    <div class="form-grid">
                        <div class="field span-2">
                            <label for="name">Nombre</label>
                            <input class="input" id="name" name="name" value="{{ old('name', $product->name) }}" required maxlength="120">
                        </div>
                        <div class="field">
                            <label for="category_id">Categoría</label>
                            <select class="select" id="category_id" name="category_id">
                                <option value="">Elegir</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="new_category">O crea una nueva</label>
                            <input class="input" id="new_category" name="new_category" value="{{ old('new_category') }}" maxlength="60" placeholder="Accesorios">
                        </div>
                        <div class="field">
                            <label for="price">Precio de venta ({{ config('tienda.moneda') }})</label>
                            <input class="input num" id="price" name="price" type="number" step="0.01" min="0" required value="{{ old('price', $product->exists ? from_cents($product->price) : '') }}">
                        </div>
                        <div class="field">
                            <label for="cost">Costo ({{ config('tienda.moneda') }})</label>
                            <input class="input num" id="cost" name="cost" type="number" step="0.01" min="0" value="{{ old('cost', $product->exists ? from_cents($product->cost) : '') }}">
                            <span class="hint">Solo se ve en el panel. Sirve para calcular la ganancia.</span>
                        </div>
                        <div class="field span-2">
                            <label for="description">Descripción</label>
                            <textarea class="textarea" id="description" name="description" maxlength="2000">{{ old('description', $product->description) }}</textarea>
                        </div>
                    </div>
                </section>

                <section class="card" data-repeater data-next="{{ count($rows) }}">
                    <div class="section-head" style="margin-bottom: 16px">
                        <h2>Tallas y existencias</h2>
                        <button class="btn" type="button" data-repeater-add>@include('partials.icon', ['name' => 'plus']) Talla</button>
                    </div>
                    <div class="stack" data-repeater-list>
                        @foreach ($rows as $i => $row)
                            <div class="variant-row" data-repeater-row>
                                <input type="hidden" name="variants[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">
                                <input type="hidden" name="variants[{{ $i }}][original_stock]" value="{{ $row['original_stock'] ?? '' }}">
                                <div class="field">
                                    <label for="size-{{ $i }}">Talla</label>
                                    <input class="input" id="size-{{ $i }}" name="variants[{{ $i }}][size]" value="{{ $row['size'] ?? '' }}" required maxlength="20" placeholder="M">
                                </div>
                                <div class="field">
                                    <label for="stock-{{ $i }}">Existencias</label>
                                    <input class="input num" id="stock-{{ $i }}" name="variants[{{ $i }}][stock]" type="number" min="0" value="{{ $row['stock'] ?? 0 }}" required>
                                </div>
                                @if (in_array($row['id'] ?? null, $soldVariantIds))
                                    <span class="icon-btn" style="cursor: help; border-style: dashed" title="Esta talla tiene ventas y se conserva en el historial." aria-hidden="true"></span>
                                @else
                                    <button class="icon-btn" type="button" data-repeater-remove aria-label="Quitar talla">@include('partials.icon', ['name' => 'x'])</button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <template>
                        <div class="variant-row" data-repeater-row>
                            <input type="hidden" name="variants[__i__][id]" value="">
                            <input type="hidden" name="variants[__i__][original_stock]" value="">
                            <div class="field">
                                <label for="size-__i__">Talla</label>
                                <input class="input" id="size-__i__" name="variants[__i__][size]" required maxlength="20" placeholder="M">
                            </div>
                            <div class="field">
                                <label for="stock-__i__">Existencias</label>
                                <input class="input num" id="stock-__i__" name="variants[__i__][stock]" type="number" min="0" value="0" required>
                            </div>
                            <button class="icon-btn" type="button" data-repeater-remove aria-label="Quitar talla">@include('partials.icon', ['name' => 'x'])</button>
                        </div>
                    </template>
                </section>

                @if ($movements->isNotEmpty())
                    <section class="card card-flush">
                        <h2 style="padding: 24px 24px 12px">Últimos movimientos</h2>
                        <div class="table-wrap">
                            <table class="table">
                                <thead><tr><th>Fecha</th><th>Talla</th><th>Tipo</th><th class="right">Cantidad</th><th class="hide-sm">Detalle</th></tr></thead>
                                <tbody>
                                    @foreach ($movements as $movement)
                                        <tr>
                                            <td class="nowrap">{{ $movement->created_at->locale('es')->isoFormat('D MMM, h:mm a') }}</td>
                                            <td>{{ $movement->variant->size }}</td>
                                            <td>{{ \App\Models\StockMovement::LABELS[$movement->type] ?? $movement->type }}</td>
                                            <td class="right num">{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</td>
                                            <td class="hide-sm muted">{{ $movement->note }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif
            </div>

            <aside class="stack-lg">
                <section class="card stack">
                    <h2>Foto</h2>
                    @if ($product->imageSrc())
                        <img class="current-photo" src="{{ $product->imageSrc() }}" alt="Foto actual">
                    @endif
                    <div class="field">
                        <label for="image">Subir foto</label>
                        <input class="input" id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
                        <span class="hint">JPG, PNG o WEBP de hasta 4 MB.</span>
                    </div>
                    <div class="field">
                        <label for="image_url">O pega un enlace</label>
                        <input class="input" id="image_url" name="image_url" type="url" value="{{ old('image_url', $product->image_url) }}" placeholder="https://">
                    </div>
                    @if ($product->image_path)
                        <label class="check"><input type="checkbox" name="remove_image" value="1"> Quitar la foto subida</label>
                    @endif
                </section>

                <section class="card stack">
                    <h2>Opciones</h2>
                    <div class="field">
                        <label for="low_stock_at">Avisar cuando queden</label>
                        <input class="input num" id="low_stock_at" name="low_stock_at" type="number" min="0" value="{{ old('low_stock_at', $product->low_stock_at) }}" required>
                        <span class="hint">Unidades por talla. Aparece en “Poco inventario”.</span>
                    </div>
                    <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active))> Visible en la tienda (si la quitas, queda archivada)</label>
                    <label class="check"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured))> Destacar en la portada</label>
                    <button class="btn btn-primary btn-block" type="submit" data-submit-once style="margin-top: 8px">Guardar</button>
                </section>
            </aside>
        </div>
    </form>
@endsection
