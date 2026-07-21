<?php

namespace App\Filament\Resources\DeliveryDocuments\Tables;

use App\Models\Customer;
use App\Models\DeliveryDocument;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class DeliveryDocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_number')->label('Numero')->searchable()->sortable(),
                TextColumn::make('order.order_number')->label('Ordine')->searchable(),
                TextColumn::make('order.customer.display_name')->label('Cliente')->searchable(['company_name', 'first_name', 'last_name']),
                TextColumn::make('issued_at')->label('Emessa il')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('payment_method_snapshot')->label('Pagamento')->placeholder('Da concordare'),
                TextColumn::make('discount_percentage')->label('Sconto')->suffix('%')->sortable(),
                TextColumn::make('total_net')->label('Netto')->money('EUR')->sortable(),
                TextColumn::make('total_tax')->label('IVA')->money('EUR')->sortable(),
                TextColumn::make('total_gross')->label('IVA inclusa')->money('EUR')->sortable(),
            ])
            ->filters([
                SelectFilter::make('customer_id')
                    ->label('Cliente')
                    ->options(fn (): array => Customer::query()->orderBy('company_name')->get()->mapWithKeys(fn (Customer $customer): array => [$customer->id => $customer->display_name])->all())
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => $query->when($data['value'] ?? null, fn (Builder $documents, $customerId): Builder => $documents->whereHas('order', fn (Builder $orders): Builder => $orders->where('customer_id', $customerId)))),
                Filter::make('issued_at')
                    ->label('Periodo')
                    ->schema([
                        DatePicker::make('from')->label('Dal'),
                        DatePicker::make('until')->label('Al'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $documents, $date): Builder => $documents->whereDate('issued_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $documents, $date): Builder => $documents->whereDate('issued_at', '<=', $date))),
            ])
            ->defaultSort('issued_at', 'desc')
            ->recordActions([
                Action::make('download')->label('Scarica')->icon('heroicon-o-arrow-down-tray')->iconButton()->tooltip('Scarica bolla')
                    ->url(fn (DeliveryDocument $record): string => route('admin.orders.delivery-document', $record->order_id))->openUrlInNewTab(),
            ])
            ->toolbarActions([
                Action::make('downloadFiltered')
                    ->label('Scarica PDF risultati filtrati')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function (ListRecords $livewire): void {
                        $ids = $livewire->getFilteredSortedTableQuery()?->pluck('delivery_documents.id')->all() ?? [];

                        if ($ids === []) {
                            return;
                        }

                        $url = route('admin.delivery-documents.export', ['documents' => implode(',', $ids)]);
                        $livewire->js('window.open('.json_encode($url).', "_blank", "noopener,noreferrer")');
                    }),
                BulkAction::make('downloadBook')
                    ->label('Scarica PDF selezionati')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function (Collection $records, Component $livewire): void {
                        $url = route('admin.delivery-documents.export', ['documents' => implode(',', $records->modelKeys())]);
                        $livewire->js('window.open('.json_encode($url).', "_blank", "noopener,noreferrer")');
                    }),
            ]);
    }
}
