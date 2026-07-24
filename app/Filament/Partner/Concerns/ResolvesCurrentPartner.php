<?php

namespace App\Filament\Partner\Concerns;

use App\Models\Partner;

trait ResolvesCurrentPartner
{
    protected static function currentPartner(): ?Partner
    {
        return auth('admin')->user()?->partner;
    }

    protected static function currentPartnerId(): int
    {
        return (int) static::currentPartner()?->getKey();
    }
}
