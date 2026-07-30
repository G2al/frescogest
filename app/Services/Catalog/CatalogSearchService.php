<?php

namespace App\Services\Catalog;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CatalogSearchService
{
    public function terms(string $search): Collection
    {
        $normalized = Str::of($search)->lower()->squish()->toString();

        if ($normalized === '') {
            return collect();
        }

        $words = preg_split('/\s+/u', $normalized) ?: [];
        $variants = collect([$normalized]);

        foreach ($words as $word) {
            foreach ($this->wordVariants($word) as $variant) {
                $variants->push(preg_replace('/\b'.preg_quote($word, '/').'\b/u', $variant, $normalized));
            }
        }

        return $variants->filter()->unique()->values();
    }

    private function wordVariants(string $word): array
    {
        if (mb_strlen($word) < 3) {
            return [];
        }

        $root = mb_substr($word, 0, -1);

        return match (mb_substr($word, -1)) {
            'a' => [$root.'e'],
            'e' => [$root.'a', $root.'i'],
            'o' => [$root.'i'],
            'i' => [$root.'o', $root.'e'],
            default => [],
        };
    }
}
