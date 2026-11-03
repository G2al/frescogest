<?php

namespace App\Services\Documents;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\Documents\Concerns\BuildsPdfLogo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class ProductCatalogPdfService
{
    use BuildsPdfLogo;

    public function stream(): Response
    {
        $groups = $this->groupedProducts();
        $generatedAt = Carbon::now();

        return Pdf::loadView('pdf.products-catalog', [
            'groups' => $groups,
            'logo' => $this->logoData(),
            'generatedAt' => $generatedAt,
            'totalProducts' => $groups->sum(fn (array $group): int => $group['products']->count()),
        ])->setPaper('a4', 'landscape')->stream('catalogo-prodotti-'.$generatedAt->format('Y-m-d').'.pdf');
    }

    private function groupedProducts()
    {
        return ProductCategory::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (ProductCategory $category): array => [
                'category' => $category,
                'products' => Product::query()
                    ->where('product_category_id', $category->id)
                    ->with('defaultUnitOfMeasure')
                    ->when(
                        $category->sort_alphabetically,
                        fn ($query) => $query->orderBy('name'),
                        fn ($query) => $query->orderBy('sort_order')->orderBy('name'),
                    )
                    ->get(),
            ])
            ->filter(fn (array $group): bool => $group['products']->isNotEmpty())
            ->values();
    }

}
