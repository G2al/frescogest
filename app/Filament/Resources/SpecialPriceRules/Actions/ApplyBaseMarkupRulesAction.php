<?php

namespace App\Filament\Resources\SpecialPriceRules\Actions;

use App\Enums\SpecialPriceScope;
use App\Models\SpecialPriceRule;
use App\Services\Pricing\SpecialPriceRuleApplier;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ApplyBaseMarkupRulesAction
{
    public static function make(): Action
    {
        return Action::make('applyBaseMarkupRules')
            ->label('Applica ricarichi di base')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Applicare i ricarichi di base a tutti i prodotti?')
            ->modalDescription('Sovrascrive il ricarico privati, ristoratori e partner su TUTTI i prodotti attivi con i valori delle 3 regole globali attive, incluse eventuali personalizzazioni fatte a mano su singoli prodotti.')
            ->modalSubmitActionLabel('Applica a tutti')
            ->action(function (): void {
                $applier = app(SpecialPriceRuleApplier::class);

                $rules = SpecialPriceRule::query()
                    ->where('scope_type', SpecialPriceScope::Global)
                    ->whereNull('partner_id')
                    ->where('active', true)
                    ->get();

                if ($rules->isEmpty()) {
                    Notification::make()
                        ->title('Nessuna regola globale attiva da applicare')
                        ->body('Crea (o riattiva) le regole con ambito "Tutti i prodotti" per privati, ristoratori e partner.')
                        ->warning()
                        ->send();

                    return;
                }

                $total = $rules->sum(fn (SpecialPriceRule $rule): int => $applier->apply($rule));

                Notification::make()
                    ->title("Ricarichi di base applicati ({$total} scritture su ".$rules->count().' regole)')
                    ->success()
                    ->send();
            });
    }
}
