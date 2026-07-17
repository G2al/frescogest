<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Models\CustomerProductPrice;
use App\Models\ProductCategory;
use App\Services\Pricing\CustomerDiscountService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PriceListRelationManager extends RelationManager
{
    protected static string $relationship = 'productPrices';

    protected static ?string $title = 'Listino prezzi';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $rules = $ownerRecord->productPrices()->whereNotNull('custom_price_per_kg')->count()
            + $ownerRecord->categoryDiscounts()->count()
            + (filled($ownerRecord->global_discount_percentage) ? 1 : 0);

        return (string) $rules;
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        return 'success';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('custom_price_per_kg')
                ->label('Prezzo manuale per questo cliente')
                ->helperText('Ha precedenza su qualsiasi sconto. Lascia vuoto per applicare lo sconto di categoria, quello generale o il prezzo base.')
                ->numeric()
                ->minValue(0.01)
                ->maxValue(99999)
                ->step(0.01)
                ->prefix('€')
                ->suffix('/kg'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['customer.categoryDiscounts', 'product.productCategory'])
                ->whereHas('product', fn (Builder $product): Builder => $product
                    ->where('active', true)
                    ->where('price_per_kg', '>', 0)))
            ->recordTitleAttribute('product.name')
            ->columns([
                TextColumn::make('product.name')
                    ->label('Prodotto')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.productCategory.name')
                    ->label('Categoria')
                    ->sortable(),
                TextColumn::make('product.price_per_kg')
                    ->label('Prezzo base')
                    ->money('EUR')
                    ->suffix('/kg')
                    ->sortable(),
                TextColumn::make('pricing_rule')
                    ->label('Regola applicata')
                    ->badge()
                    ->getStateUsing(fn (CustomerProductPrice $record): string => $this->pricingRuleLabel($record))
                    ->color(fn (CustomerProductPrice $record): string => match ($record->pricing_rule['source']) {
                        'product' => 'warning',
                        'category' => 'info',
                        'global' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('effective_price_per_kg')
                    ->label('Prezzo cliente')
                    ->money('EUR')
                    ->suffix('/kg')
                    ->weight('bold'),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Categoria')
                    ->options(fn (): array => ProductCategory::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, int|string $categoryId): Builder => $query
                            ->whereHas('product', fn (Builder $product): Builder => $product
                                ->where('product_category_id', $categoryId)),
                    )),
                TernaryFilter::make('custom_price_per_kg')
                    ->label('Prezzo manuale')
                    ->trueLabel('Con prezzo manuale')
                    ->falseLabel('Senza prezzo manuale')
                    ->nullable(),
            ])
            ->defaultSort('product.name')
            ->headerActions([
                Action::make('globalDiscount')
                    ->label('Sconto generale')
                    ->icon('heroicon-o-receipt-percent')
                    ->color('success')
                    ->schema([
                        TextInput::make('percentage')
                            ->label('Sconto su tutti i prodotti')
                            ->helperText('Inserisci 0 per rimuovere lo sconto generale.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%')
                            ->required(),
                    ])
                    ->fillForm(fn (): array => [
                        'percentage' => $this->getOwnerRecord()->global_discount_percentage ?? 0,
                    ])
                    ->action(function (array $data): void {
                        app(CustomerDiscountService::class)->setGlobal(
                            $this->getOwnerRecord(),
                            $data['percentage'],
                        );
                        $this->notify('Sconto generale aggiornato');
                    }),
                Action::make('categoryDiscount')
                    ->label('Sconto categoria')
                    ->icon('heroicon-o-tag')
                    ->color('info')
                    ->schema([
                        Select::make('product_category_id')
                            ->label('Categoria')
                            ->options(fn (): array => ProductCategory::query()
                                ->where('active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required(),
                        TextInput::make('percentage')
                            ->label('Sconto categoria')
                            ->helperText('Inserisci 0 per rimuovere lo sconto dalla categoria selezionata.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%')
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        app(CustomerDiscountService::class)->setCategory(
                            $this->getOwnerRecord(),
                            (int) $data['product_category_id'],
                            $data['percentage'],
                        );
                        $this->notify('Sconto categoria aggiornato');
                    }),
                Action::make('resetManualPrices')
                    ->label('Rimuovi prezzi manuali')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (): void {
                        app(CustomerDiscountService::class)->resetManualPrices($this->getOwnerRecord());
                        $this->notify('Prezzi manuali rimossi');
                    }),
                Action::make('resetAllPricing')
                    ->label('Ripristina tutto')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Verranno rimossi lo sconto generale, gli sconti categoria e tutti i prezzi manuali del cliente.')
                    ->action(function (): void {
                        app(CustomerDiscountService::class)->resetAll($this->getOwnerRecord());
                        $this->notify('Listino cliente ripristinato');
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Modifica prezzo')
                    ->iconButton()
                    ->tooltip('Imposta prezzo manuale'),
            ]);
    }

    private function pricingRuleLabel(CustomerProductPrice $record): string
    {
        $details = $record->pricing_rule;

        return match ($details['source']) {
            'product' => 'Prezzo manuale',
            'category' => 'Categoria -'.$this->formatPercentage($details['discount_percentage']).'%',
            'global' => 'Generale -'.$this->formatPercentage($details['discount_percentage']).'%',
            default => 'Prezzo base',
        };
    }

    private function formatPercentage(string|int|float|null $percentage): string
    {
        return rtrim(rtrim(number_format((float) $percentage, 2, ',', ''), '0'), ',');
    }

    private function notify(string $title): void
    {
        Notification::make()
            ->success()
            ->title($title)
            ->send();
    }
}
