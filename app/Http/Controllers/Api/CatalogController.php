<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\Pricing\ProductPricingService;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function categories()
    {
        return ProductCategoryResource::collection(
            ProductCategory::query()->publicCatalog()->orderBy('sort_order')->orderBy('name')->get(),
        );
    }

    public function products(Request $request, ProductPricingService $pricing)
    {
        $products = Product::query()
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
            ->orderBy('name')
            ->paginate(12);

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
