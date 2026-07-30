<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function mount(): void
    {
        if (auth('admin')->user()?->hasPartnerPanelRole()) {
            $this->redirect(PartnerReports::getUrl(panel: 'admin'), navigate: true);
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth('admin')->user()?->hasAdminPanelRole() === true;
    }
}
