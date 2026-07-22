<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasGeneratedSlug
{
    public static function bootHasGeneratedSlug(): void
    {
        static::saving(function (Model $model): void {
            if (filled($model->getAttribute('slug'))) {
                return;
            }

            $baseSlug = Str::slug((string) $model->getAttribute('name')) ?: 'elemento';
            $slug = $baseSlug;
            $suffix = 2;

            while ($model->newQueryWithoutScopes()
                ->where('slug', $slug)
                ->when($model->exists, fn ($query) => $query->whereKeyNot($model->getKey()))
                ->exists()) {
                $slug = $baseSlug.'-'.$suffix++;
            }

            $model->setAttribute('slug', $slug);
        });
    }
}
