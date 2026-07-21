<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\CatalogProductRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\UnitOfMeasure;
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

    public function products(CatalogProductRequest $request, ProductPricingService $pricing)
    {
        $query = Product::query()
            ->publicCatalog()
            ->with(['productCategory', 'defaultUnitOfMeasure'])
            ->when($request->filled('category'), fn ($query) => $query->whereHas(
                'productCategory',
                fn ($category) => $category->where('slug', $request->string('category')->toString()),
            ))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.$request->string('search')->trim()->toString().'%';
                $query->where(function ($products) use ($search): void {
                    $products
                        ->where('name', 'like', $search)
                        ->orWhere('code', 'like', $search)
                        ->orWhere('public_description', 'like', $search);
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
            default => $query->orderBy('sort_order')->orderBy('name'),
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
            ->with(['productCategory', 'defaultUnitOfMeasure'])
            ->where('slug', $slug)
            ->firstOrFail();

        $pricing->apply(collect([$product]), $request->user('customer')?->customer);

        return new ProductResource($product);
    }
}
