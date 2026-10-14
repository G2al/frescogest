<?php

namespace Tests\Feature;

use App\Enums\CustomerType;
use App\Filament\Resources\DeliveryDocuments\Pages\ListDeliveryDocuments;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Services\Documents\CreateDeliveryDocumentService;
use App\Services\Orders\CreateManualOrderService;
use Database\Seeders\CompanySeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Segnalazione del cliente: genera una bolla, la conferma e la segna pagata, poi si
 * accorge di aver sbagliato la quantità di un prodotto. Apre "Modifica", cambia il
 * peso, clicca Salva: la quantità non cambia.
 *
 * Interagendo con il campo esistente del Repeater (come fa davvero il browser), il
 * salvataggio funziona correttamente: non c'è un bug nella logica di aggiornamento.
 * Il sospetto più probabile è una corsa tra il debounce di 300ms sui campi
 * quantità/prezzo e il click su "Salva": se si clicca entro i 300ms, il valore
 * digitato potrebbe non essere ancora sincronizzato quando parte il salvataggio.
 * Per questo i campi sono stati cambiati da live(debounce: 300) a live(onBlur: true),
 * che sincronizza il valore appena il campo perde il focus (incluso il click su
 * Salva) invece che dopo un timer.
 */
class EditOrderDeliveryDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_the_quantity_of_a_paid_order_and_saving_actually_updates_it(): void
    {
        $this->seed([CompanySeeder::class, UserSeeder::class]);
        $admin = User::query()->where('panel_role', 'admin')->firstOrFail();

        $category = ProductCategory::query()->firstOrCreate(['name' => 'Verdura'], ['active' => true]);
        $taxRate = TaxRate::query()->firstOrCreate(['percentage' => 4], ['name' => 'IVA 4%', 'active' => true]);
        $unit = UnitOfMeasure::query()->firstOrCreate(['symbol' => 'kg'], ['name' => 'Chilogrammi', 'active' => true]);
        $onion = Product::create([
            'product_category_id' => $category->id,
            'tax_rate_id' => $taxRate->id,
            'default_unit_of_measure_id' => $unit->id,
            'name' => 'Cipolle Bianche',
            'purchase_cost_per_unit' => 1,
            'markup_percentage' => 75,
            'active' => true,
        ]);
        $customer = Customer::factory()->create(['type' => CustomerType::Restaurant]);

        $order = app(CreateManualOrderService::class)->create([
            'customer_id' => $customer->id,
            'status' => 'paid',
            'requested_at' => now(),
            'items' => [['product_id' => $onion->id, 'quantity' => 10, 'unit_price_net' => 2]],
        ]);
        $document = app(CreateDeliveryDocumentService::class)->create($order, $admin, ['issued_at' => now()]);

        $this->actingAs($admin, 'admin');

        $test = Livewire::test(ListDeliveryDocuments::class)
            ->mountTableAction('editOrderDeliveryDocument', $document);

        $itemKey = array_key_first($test->instance()->mountedActions[0]['data']['items']);

        $test->set("mountedActions.0.data.items.{$itemKey}.quantity", 5)
            ->callMountedTableAction()
            ->assertHasNoActionErrors();

        $order->refresh();

        $this->assertSame(1, $order->items->count());
        $this->assertSame('5.000', $order->items->first()->quantity);
    }
}
