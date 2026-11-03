<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::with(['variants', 'category'])
            ->search($request->query('q'))
            ->when($request->query('categoria'), fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->query('filtro') === 'bajo', fn ($q) => $q->active()->lowStock())
            ->when($request->query('filtro') === 'archivados', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $totals = ProductVariant::query()
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->where('products.is_active', true)
            ->selectRaw('COALESCE(SUM(product_variants.stock), 0) as units')
            ->selectRaw('COALESCE(SUM(product_variants.stock * products.cost), 0) as cost_value')
            ->selectRaw('COALESCE(SUM(product_variants.stock * products.price), 0) as sale_value')
            ->first();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::orderBy('position')->get(),
            'totals' => $totals,
            'activeCount' => Product::active()->count(),
            'lowStockCount' => Product::active()->lowStock()->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.form', [
            'product' => new Product(['is_active' => true, 'low_stock_at' => 3]),
            'categories' => Category::orderBy('position')->get(),
            'movements' => collect(),
            'soldVariantIds' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $product = DB::transaction(function () use ($request, $data) {
            $product = Product::create($this->attributes($request, $data) + [
                'slug' => Product::uniqueSlug($data['name']),
            ]);

            $this->syncVariants($product, $data['variants'], $request->user()->id);

            return $product;
        });

        return redirect()->route('admin.products.edit', $product)->with('status', 'Prenda guardada en el inventario.');
    }

    public function edit(Product $product): View
    {
        $product->load('variants');

        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::orderBy('position')->get(),
            'movements' => StockMovement::with(['variant', 'order'])
                ->whereIn('product_variant_id', $product->variants->pluck('id'))
                ->latest('id')
                ->take(12)
                ->get(),
            'soldVariantIds' => DB::table('order_items')
                ->whereIn('product_variant_id', $product->variants->pluck('id'))
                ->distinct()
                ->pluck('product_variant_id')
                ->all(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request);
        $oldImage = $product->image_path;

        DB::transaction(function () use ($request, $data, $product) {
            $product->update($this->attributes($request, $data, $product));

            $this->syncVariants($product, $data['variants'], $request->user()->id);
        });

        if ($oldImage && $oldImage !== $product->image_path) {
            Storage::disk('public')->delete($oldImage);
        }

        return back()->with('status', 'Cambios guardados.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category_id' => ['nullable', 'required_without:new_category', 'integer', 'exists:categories,id'],
            'new_category' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'low_stock_at' => ['required', 'integer', 'min:0', 'max:1000'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'image_url' => ['nullable', 'url:https', 'max:2048'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.size' => ['required', 'string', 'max:20', 'distinct:ignore_case'],
            'variants.*.stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'variants.*.original_stock' => ['nullable', 'integer', 'min:0'],
        ], [
            'variants.required' => 'Agrega al menos una talla.',
            'variants.*.size.required' => 'Escribe la talla.',
            'variants.*.size.distinct' => 'Hay tallas repetidas.',
            'category_id.required_without' => 'Elige una categoría o escribe una nueva.',
        ]);
    }

    /** @return array<string, mixed> */
    private function attributes(Request $request, array $data, ?Product $product = null): array
    {
        $categoryId = $data['category_id'] ?? null;

        if (filled($data['new_category'] ?? null)) {
            $categoryId = Category::firstOrCreate(
                ['slug' => Str::slug($data['new_category'])],
                ['name' => $data['new_category'], 'position' => (int) Category::max('position') + 1],
            )->id;
        }

        $attributes = [
            'category_id' => $categoryId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => to_cents($data['price']),
            'cost' => to_cents($data['cost'] ?? 0),
            'low_stock_at' => $data['low_stock_at'],
            'is_active' => $request->boolean('is_active'),
            'is_featured' => $request->boolean('is_featured'),
            'image_url' => $data['image_url'] ?? null,
        ];

        if ($request->hasFile('image')) {
            $attributes['image_path'] = $request->file('image')->store('products', 'public');
        } elseif ($request->boolean('remove_image')) {
            $attributes['image_path'] = null;
        } elseif ($product) {
            $attributes['image_path'] = $product->image_path;
        }

        return $attributes;
    }

    /**
     * Las existencias se aplican como diferencia contra lo que el formulario
     * mostró al abrirse, así una venta hecha mientras se editaba no se pierde.
     */
    private function syncVariants(Product $product, array $rows, int $userId): void
    {
        $existing = $product->variants()->lockForUpdate()->get()->keyBy('id');
        $kept = [];

        foreach ($rows as $row) {
            $variant = filled($row['id'] ?? null) ? $existing->get((int) $row['id']) : null;

            if (! $variant) {
                $variant = $product->variants()->create(['size' => $row['size'], 'stock' => $row['stock']]);
                $kept[] = $variant->id;

                if ($row['stock'] > 0) {
                    $this->logMovement($variant, 'entrada', $row['stock'], 'Registro inicial', $userId);
                }

                continue;
            }

            $kept[] = $variant->id;
            $delta = (int) $row['stock'] - (int) ($row['original_stock'] ?? $variant->stock);

            if ($variant->stock + $delta < 0) {
                throw ValidationException::withMessages([
                    'variants' => "La talla {$variant->size} cambió mientras editabas: hoy tiene {$variant->stock}. Revisa el número.",
                ]);
            }

            $variant->update(['size' => $row['size'], 'stock' => $variant->stock + $delta]);

            if ($delta !== 0) {
                $this->logMovement($variant, $delta > 0 ? 'entrada' : 'ajuste', $delta, 'Edición de inventario', $userId);
            }
        }

        // Las tallas con ventas se conservan para no romper el historial.
        foreach ($existing->except($kept) as $removed) {
            if (! $removed->orderItems()->exists()) {
                $removed->delete();
            }
        }
    }

    private function logMovement(ProductVariant $variant, string $type, int $quantity, string $note, int $userId): void
    {
        StockMovement::create([
            'product_variant_id' => $variant->id,
            'user_id' => $userId,
            'type' => $type,
            'quantity' => $quantity,
            'note' => $note,
        ]);
    }
}
