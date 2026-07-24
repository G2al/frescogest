<?php

namespace App\Filament\Partner\Resources\GoodsEntries;

use App\Filament\Partner\Concerns\ResolvesCurrentPartner;
use App\Filament\Partner\Resources\GoodsEntries\Pages\ListGoodsEntries;
use App\Models\PartnerGoodsEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GoodsEntryResource extends Resource
{
    use ResolvesCurrentPartner;

    protected static ?string $model = PartnerGoodsEntry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Merce ricevuta';

    protected static ?string $modelLabel = 'carico';

    protected static ?string $pluralModelLabel = 'merce ricevuta';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('delivered_on')->label('Data')->date('d/m/Y')->sortable(),
                TextColumn::make('product.name')->label('Prodotto')->searchable()->sortable(),
                TextColumn::make('quantity')->label('Quantità')->numeric(3),
                TextColumn::make('product.defaultUnitOfMeasure.symbol')->label('Unità'),
                TextColumn::make('unit_purchase_price_net')->label('Prezzo netto')->money('EUR'),
                TextColumn::make('tax_percentage')->label('IVA')->suffix('%'),
                TextColumn::make('total_gross')->label('Totale IVA inclusa')->money('EUR')->sortable(),
            ])
            ->filters([
                SelectFilter::make('product_id')->label('Prodotto')->relationship('product', 'name')->searchable()->preload(),
            ])
            ->defaultSort('delivered_on', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('partner_id', static::currentPartnerId())
            ->with(['product.defaultUnitOfMeasure']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ListGoodsEntries::route('/')];
    }
}
