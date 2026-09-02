<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\CatalogProductRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Producer;
use App\Models\UnitOfMeasure;
use App\Services\Catalog\CatalogSearchService;
use App\Services\Pricing\ProductPricingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function categories()
    {
        return ProductCategoryResource::collection(
            ProductCategory::query()
                ->publicCatalog()
                ->withCount(['products' => fn (Builder $products): Builder => $products->publicCatalog()])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        );
    }

    public function producers(Request $request)
    {
        $category = ProductCategory::query()
            ->publicCatalog()
            ->where('slug', $request->string('category')->toString())
            ->first();

        if (! $category) {
            return response()->json(['data' => []]);
        }

        $producers = Producer::query()
            ->publicCatalog()
            ->where('product_category_id', $category->id)
            ->withCount(['products' => fn (Builder $products): Builder => $products->publicCatalog()])
            ->having('products_count', '>', 0)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'sort_order']);

        return response()->json([
            'data' => $producers->map(fn (Producer $producer): array => [
                'name' => $producer->name,
                'slug' => $producer->slug,
                'products_count' => $producer->products_count,
            ]),
        ]);
    }

    public function filters()
    {
        $catalog = Product::query()->publicCatalog();

        return response()->json([
            'data' => [
                'price' => [
                    'min' => round((float) (clone $catalog)->min('base_price_per_unit'), 2),
                    'max' => round((float) (clone $catalog)->max('base_price_per_unit'), 2),
                ],
                'seasonal_count' => (clone $catalog)->where('is_seasonal', true)->count(),
                'units' => UnitOfMeasure::query()
                    ->where('active', true)
                    ->whereHas('products', fn (Builder $products): Builder => $products->publicCatalog())
                    ->withCount(['products as products_count' => fn (Builder $products): Builder => $products->publicCatalog()])
                    ->orderBy('name')
                    ->get(['id', 'name', 'symbol'])
                    ->map(fn (UnitOfMeasure $unit): array => [
                        'id' => $unit->id,
                        'name' => $unit->name,
                        'symbol' => $unit->symbol,
                        'products_count' => $unit->products_count,
                    ]),
            ],
        ]);
    }

    public function products(CatalogProductRequest $request, ProductPricingService $pricing, CatalogSearchService $searchService)
    {
        $category = $request->filled('category')
            ? ProductCategory::query()->where('slug', $request->string('category')->toString())->first()
            : null;

        $query = Product::query()
            ->publicCatalog()
            ->with(['productCategory', 'defaultUnitOfMeasure', 'producer'])
            ->when($category, fn ($query) => $query->where('product_category_id', $category->id))
            ->when($request->filled('producer'), fn ($query) => $query->whereHas(
                'producer',
                fn ($producer) => $producer->where('slug', $request->string('producer')->toString()),
            ))
            ->when($request->filled('search'), function ($query) use ($request, $searchService): void {
                $terms = $searchService->terms($request->string('search')->toString());
                $query->where(function ($products) use ($terms): void {
                    foreach ($terms as $term) {
                        $search = '%'.$term.'%';
                        $products->orWhere(function ($variant) use ($search): void {
                            $variant
                                ->where('name', 'like', $search)
                                ->orWhere('code', 'like', $search)
                                ->orWhere('public_description', 'like', $search);
                        });
                    }
                });
            })
            ->when($request->boolean('seasonal'), fn ($query) => $query->where('is_seasonal', true))
            ->when($request->filled('unit'), fn ($query) => $query->where('default_unit_of_measure_id', $request->integer('unit')))
            ->when($request->filled('min_price'), fn ($query) => $query->where('base_price_per_unit', '>=', $request->float('min_price')))
            ->when($request->filled('max_price'), fn ($query) => $query->where('base_price_per_unit', '<=', $request->float('max_price')));

        match ($request->string('sort', 'relevant')->toString()) {
            'name_asc' => $query->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            'price_asc' => $query->orderBy('base_price_per_unit')->orderBy('name'),
            'price_desc' => $query->orderByDesc('base_price_per_unit')->orderBy('name'),
            default => $category?->sort_alphabetically
                ? $query->orderBy('name')
                : $query->orderBy('sort_order')->orderBy('name'),
        };

        $products = $query->paginate(12);

        $products->setCollection($pricing->apply(
            $products->getCollection(),
            $request->user('customer')?->customer,
        ));

        return ProductResource::collection($products);
    }

    public function product(Request $request, string $slug, ProductPricingService $pricing): ProductResource
    {
        $product = Product::query()
            ->publicCatalog()
            ->with(['productCategory', 'defaultUnitOfMeasure', 'producer'])
            ->where('slug', $slug)
            ->firstOrFail();

        $pricing->apply(collect([$product]), $request->user('customer')?->customer);

        return new ProductResource($product);
    }
}
