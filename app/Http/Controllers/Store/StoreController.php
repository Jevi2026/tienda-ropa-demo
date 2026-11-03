<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function home(): View
    {
        $categories = Category::orderBy('position')->get();

        $sections = $categories->map(fn (Category $category) => (object) [
            'category' => $category,
            'products' => Product::active()->with('variants')->where('category_id', $category->id)->latest('id')->take(4)->get(),
        ])->filter(fn ($section) => $section->products->isNotEmpty());

        return view('store.home', [
            'categories' => $sections->pluck('category'),
            'covers' => $sections->mapWithKeys(fn ($s) => [$s->category->id => $s->products->first()->imageSrc()]),
            'sections' => $sections,
            'featured' => Product::active()->where('is_featured', true)->take(5)->get(),
        ]);
    }

    public function catalog(Request $request): View
    {
        $category = Category::where('slug', $request->query('categoria'))->first();

        $products = Product::active()
            ->with('variants')
            ->when($category, fn ($q) => $q->where('category_id', $category->id))
            ->search($request->query('q'))
            ->latest('id')
            ->paginate(16)
            ->withQueryString();

        return view('store.catalog', [
            'products' => $products,
            'categories' => Category::orderBy('position')->get(),
            'category' => $category,
            'term' => $request->query('q'),
        ]);
    }

    public function product(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load('variants', 'category');

        return view('store.product', [
            'product' => $product,
            'related' => Product::active()
                ->with('variants')
                ->where('category_id', $product->category_id)
                ->whereKeyNot($product->id)
                ->take(4)
                ->get(),
        ]);
    }
}
