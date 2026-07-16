<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Services\Pricing\CustomerPriceListService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PriceListRelationManager extends RelationManager
{
    protected static string $relationship = 'productPrices';

    protected static ?string $title = 'Listino prezzi';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->productPrices()->whereNotNull('custom_price_per_kg')->count();
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        return 'success';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('custom_price_per_kg')
                ->label('Prezzo personalizzato')
                ->helperText('Lascia vuoto per utilizzare il prezzo base del prodotto.')
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
            ->modifyQueryUsing(fn ($query) => $query
                ->with(['product.productCategory'])
                ->whereHas('product', fn ($product) => $product
                    ->where('active', true)
                    ->where('price_per_kg', '>', 0)))
            ->recordTitleAttribute('product.name')
            ->columns([
                TextColumn::make('product.name')->label('Prodotto')->searchable()->sortable(),
                TextColumn::make('product.productCategory.name')->label('Categoria')->sortable(),
                TextColumn::make('product.price_per_kg')->label('Prezzo base')->money('EUR')->suffix('/kg')->sortable(),
                TextColumn::make('custom_price_per_kg')
                    ->label('Prezzo personalizzato')
                    ->money('EUR')
                    ->placeholder('Prezzo base')
                    ->sortable(),
                TextColumn::make('effective_price_per_kg')
                    ->label('Prezzo effettivo')
                    ->money('EUR')
                    ->weight('bold'),
            ])
            ->defaultSort('product.name')
            ->headerActions([
                Action::make('resetPriceList')
                    ->label('Ripristina prezzi base')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (): void {
                        app(CustomerPriceListService::class)->resetCustomer($this->getOwnerRecord());
                        Notification::make()->success()->title('Listino ripristinato')->send();
                    }),
            ])
            ->recordActions([
                EditAction::make()->label('Modifica prezzo'),
            ]);
    }
}
