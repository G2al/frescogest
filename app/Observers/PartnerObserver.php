<?php

namespace App\Observers;

use App\Models\Partner;
use App\Services\Partners\PartnerPriceListService;

class PartnerObserver
{
    public function created(Partner $partner): void
    {
        app(PartnerPriceListService::class)->syncPartner($partner);
    }
}
