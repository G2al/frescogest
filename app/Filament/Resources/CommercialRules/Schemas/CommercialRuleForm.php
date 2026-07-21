<?php

namespace App\Filament\Resources\CommercialRules\Schemas;

use App\Enums\CustomerType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CommercialRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Regola di vendita e consegna')->columnSpanFull()->schema([
                TextInput::make('name')->label('Nome')->required()->maxLength(255),
                Select::make('customer_type')->label('Tipo cliente')->options(CustomerType::options())->required(),
                TextInput::make('province')->label('Provincia')->helperText('Lascia vuoto per applicare la regola a tutta Italia.')->maxLength(2),
                TextInput::make('postal_code_pattern')->label('CAP o zona CAP')->helperText('Supporta il carattere *: ad esempio 20* applica la regola a tutti i CAP che iniziano per 20.')->maxLength(20),
                TextInput::make('minimum_order_gross')->label('Spesa minima IVA inclusa')->numeric()->minValue(0)->prefix('€')->required(),
                TextInput::make('free_shipping_threshold_gross')->label('Consegna gratuita da')->numeric()->minValue(0)->prefix('€'),
                TextInput::make('shipping_fee_net')->label('Costo consegna netto')->numeric()->minValue(0)->prefix('€')->required(),
                Select::make('shipping_tax_rate_id')->label('IVA consegna')->relationship('shippingTaxRate', 'name')->searchable()->preload(),
                TextInput::make('priority')->label('Priorità')->numeric()->default(0)->required(),
                Toggle::make('active')->label('Attiva')->default(true),
            ])->columns(2),
        ]);
    }
}
