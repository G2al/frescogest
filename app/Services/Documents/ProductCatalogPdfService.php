<?php

namespace App\Services\Documents;

use App\Models\Product;
use App\Models\ProductCategory;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class ProductCatalogPdfService
{
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

    private function logoData(): ?array
    {
        $path = public_path('assets/images/new-logo-primary.png');

        if (! is_file($path)) {
            return null;
        }

        $contents = (string) file_get_contents($path);
        $contents = $this->cropTransparentLogo($contents) ?? $contents;
        $size = getimagesizefromstring($contents);

        if ($size === false) {
            return null;
        }

        $scale = min(300 / $size[0], 70 / $size[1]);

        return [
            'data' => 'data:'.($size['mime'] ?? 'image/png').';base64,'.base64_encode($contents),
            'width' => max(1, (int) round($size[0] * $scale)),
            'height' => max(1, (int) round($size[1] * $scale)),
        ];
    }

    private function cropTransparentLogo(string $contents): ?string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagecrop')) {
            return null;
        }

        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $bounds = [$width, $height, -1, -1];

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if (((imagecolorat($image, $x, $y) >> 24) & 0x7F) >= 120) {
                    continue;
                }

                $bounds = [min($bounds[0], $x), min($bounds[1], $y), max($bounds[2], $x), max($bounds[3], $y)];
            }
        }

        if ($bounds[2] < $bounds[0] || $bounds[3] < $bounds[1]) {
            imagedestroy($image);

            return null;
        }

        $padding = max(4, (int) round(max($width, $height) * .01));
        $x = max(0, $bounds[0] - $padding);
        $y = max(0, $bounds[1] - $padding);
        $cropped = imagecrop($image, [
            'x' => $x,
            'y' => $y,
            'width' => min($width - $x, $bounds[2] - $bounds[0] + 1 + ($padding * 2)),
            'height' => min($height - $y, $bounds[3] - $bounds[1] + 1 + ($padding * 2)),
        ]);
        imagedestroy($image);

        if ($cropped === false) {
            return null;
        }

        imagealphablending($cropped, false);
        imagesavealpha($cropped, true);
        ob_start();
        imagepng($cropped);
        $result = ob_get_clean();
        imagedestroy($cropped);

        return is_string($result) ? $result : null;
    }
}
