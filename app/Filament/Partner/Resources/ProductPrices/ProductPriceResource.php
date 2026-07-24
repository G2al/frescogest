<?php

namespace App\Filament\Partner\Resources\ProductPrices;

use App\Filament\Partner\Concerns\ResolvesCurrentPartner;
use App\Filament\Partner\Resources\ProductPrices\Pages\ListProductPrices;
use App\Models\PartnerProductPrice;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductPriceResource extends Resource
{
    use ResolvesCurrentPartner;

    protected static ?string $model = PartnerProductPrice::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Il mio listino';

    protected static ?string $modelLabel = 'prezzo';

    protected static ?string $pluralModelLabel = 'listino prodotti';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')->label('Prodotto')->searchable()->sortable(),
                TextColumn::make('product.productCategory.name')->label('Categoria')->sortable(),
                TextColumn::make('purchase_price_net')->label('Pago netto')->money('EUR')->sortable(),
                TextColumn::make('purchase_price_gross')->label('Pago IVA inclusa')->money('EUR'),
                TextColumn::make('sale_price_net')->label('Vendo netto')->money('EUR')->sortable(),
                TextColumn::make('sale_price_gross')->label('Vendo IVA inclusa')->money('EUR'),
                TextColumn::make('margin_net')->label('Guadagno netto')->money('EUR'),
                TextColumn::make('markup_percentage')->label('Ricarico')->suffix('%')->sortable(),
            ])
            ->filters([
                SelectFilter::make('product_id')->label('Prodotto')->relationship('product', 'name')->searchable()->preload(),
            ])
            ->defaultSort('product.name');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('partner_id', static::currentPartnerId())
            ->with(['product.productCategory', 'product.taxRate']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ListProductPrices::route('/')];
    }
}
