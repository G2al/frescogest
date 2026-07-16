<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = ProductCategory::query()->pluck('id', 'name');
        $taxRates = TaxRate::query()
            ->get()
            ->mapWithKeys(fn (TaxRate $taxRate): array => [(int) $taxRate->percentage => $taxRate->id]);
        $units = UnitOfMeasure::query()->pluck('id', 'symbol');

        foreach ($this->products() as $sortOrder => $product) {
            Product::updateOrCreate(
                ['code' => $product['code']],
                [
                    'product_category_id' => $categories->get($product['category']),
                    'tax_rate_id' => $taxRates->get($product['tax']),
                    'default_unit_of_measure_id' => $units->get($product['unit']),
                    'name' => $product['name'],
                    'slug' => Str::slug($product['name']),
                    'description' => $product['description'],
                    'public_description' => $product['description'],
                    'price_per_kg' => $this->prices()[$product['code']],
                    'is_public' => true,
                    'is_seasonal' => $product['seasonal'] ?? false,
                    'sort_order' => $sortOrder,
                    'notes' => null,
                    'active' => true,
                ],
            );
        }
    }

    private function products(): array
    {
        return [
            ['code' => 'LAT-001', 'category' => 'Latticini', 'name' => 'Mozzarella di bufala campana', 'description' => 'Mozzarella di bufala campana fresca, dal gusto delicato e dalla consistenza morbida.', 'tax' => 4, 'unit' => 'kg'],
            ['code' => 'LAT-002', 'category' => 'Latticini', 'name' => 'Fior di latte', 'description' => 'Fior di latte vaccino fresco, ideale per pizza, cucina e consumo al naturale.', 'tax' => 4, 'unit' => 'kg'],
            ['code' => 'LAT-003', 'category' => 'Latticini', 'name' => 'Ricotta vaccina', 'description' => 'Ricotta vaccina fresca, cremosa e dal sapore equilibrato.', 'tax' => 4, 'unit' => 'kg'],
            ['code' => 'LAT-004', 'category' => 'Latticini', 'name' => 'Ricotta di bufala', 'description' => 'Ricotta di bufala fresca, ricca e particolarmente cremosa.', 'tax' => 4, 'unit' => 'kg'],
            ['code' => 'LAT-005', 'category' => 'Latticini', 'name' => 'Caciocavallo', 'description' => 'Formaggio a pasta filata dal gusto intenso, adatto al taglio e alla cucina.', 'tax' => 4, 'unit' => 'kg'],
            ['code' => 'LAT-006', 'category' => 'Latticini', 'name' => 'Provola affumicata', 'description' => 'Provola affumicata a pasta filata, profumata e saporita.', 'tax' => 4, 'unit' => 'kg'],
            ['code' => 'LAT-007', 'category' => 'Latticini', 'name' => 'Scamorza bianca', 'description' => 'Scamorza bianca dalla consistenza compatta e dal gusto delicato.', 'tax' => 4, 'unit' => 'kg'],
            ['code' => 'LAT-008', 'category' => 'Latticini', 'name' => 'Scamorza affumicata', 'description' => 'Scamorza affumicata dal profumo deciso, ideale da fondere o grigliare.', 'tax' => 4, 'unit' => 'kg'],
            ['code' => 'LAT-009', 'category' => 'Latticini', 'name' => 'Burrata', 'description' => 'Burrata fresca con cuore cremoso di stracciatella.', 'tax' => 4, 'unit' => 'pz'],
            ['code' => 'LAT-010', 'category' => 'Latticini', 'name' => 'Stracciatella', 'description' => 'Stracciatella fresca di pasta filata e panna, morbida e cremosa.', 'tax' => 4, 'unit' => 'vaschetta'],
            ['code' => 'LAT-011', 'category' => 'Latticini', 'name' => 'Mascarpone', 'description' => 'Mascarpone fresco dalla consistenza vellutata, ideale per cucina e pasticceria.', 'tax' => 4, 'unit' => 'vaschetta'],
            ['code' => 'LAT-012', 'category' => 'Latticini', 'name' => 'Parmigiano Reggiano', 'description' => 'Parmigiano Reggiano stagionato, disponibile in porzioni professionali.', 'tax' => 4, 'unit' => 'kg'],
            ['code' => 'FRU-001', 'category' => 'Frutta', 'name' => 'Mele Golden', 'description' => 'Mele Golden croccanti, dolci e aromatiche.', 'tax' => 4, 'unit' => 'kg'],
            ['code' => 'FRU-002', 'category' => 'Frutta', 'name' => 'Pere Abate', 'description' => 'Pere Abate succose, profumate e dalla polpa fine.', 'tax' => 4, 'unit' => 'kg'],
            ['code' => 'FRU-003', 'category' => 'Frutta', 'name' => 'Banane', 'description' => 'Banane selezionate con maturazione uniforme.', 'tax' => 4, 'unit' => 'kg'],
            ['code' => 'FRU-004', 'category' => 'Frutta', 'name' => 'Prugne', 'description' => 'Prugne fresche dalla polpa dolce e succosa.', 'tax' => 4, 'unit' => 'kg', 'seasonal' => true],
            ['code' => 'FRU-005', 'category' => 'Frutta', 'name' => 'Albicocche', 'description' => 'Albicocche fresche, profumate e naturalmente dolci.', 'tax' => 4, 'unit' => 'kg', 'seasonal' => true],
            ['code' => 'FRU-006', 'category' => 'Frutta', 'name' => 'Arance', 'description' => 'Arance da tavola succose, ideali anche per spremute.', 'tax' => 4, 'unit' => 'kg', 'seasonal' => true],
            ['code' => 'FRU-007', 'category' => 'Frutta', 'name' => 'Limoni', 'description' => 'Limoni freschi e profumati, selezionati per cucina e bevande.', 'tax' => 4, 'unit' => 'kg'],
            ['code' => 'FRU-008', 'category' => 'Frutta', 'name' => 'Pesche gialle', 'description' => 'Pesche gialle dalla polpa soda, succosa e profumata.', 'tax' => 4, 'unit' => 'kg', 'seasonal' => true],
            ['code' => 'FRU-009', 'category' => 'Frutta', 'name' => 'Kiwi', 'description' => 'Kiwi dalla polpa verde e dal gusto fresco e leggermente acidulo.', 'tax' => 4, 'unit' => 'kg'],
            ['code' => 'FRU-010', 'category' => 'Frutta', 'name' => 'Uva bianca', 'description' => 'Uva bianca da tavola con acini croccanti e dolci.', 'tax' => 4, 'unit' => 'kg', 'seasonal' => true],
            ['code' => 'VER-001', 'category' => 'Verdura', 'name' => 'Pomodori ramati', 'description' => 'Pomodori ramati maturi e consistenti, adatti a insalate e cucina.', 'tax' => 4, 'unit' => 'kg'],
            ['code' => 'VER-002', 'category' => 'Verdura', 'name' => 'Zucchine', 'description' => 'Zucchine fresche e tenere, selezionate per pezzatura.', 'tax' => 4, 'unit' => 'kg'],
            ['code' => 'VER-003', 'category' => 'Verdura', 'name' => 'Melanzane', 'description' => 'Melanzane dalla polpa compatta, ideali per griglia e cucina.', 'tax' => 4, 'unit' => 'kg'],
            ['code' => 'VER-004', 'category' => 'Verdura', 'name' => 'Peperoni misti', 'description' => 'Peperoni rossi e gialli carnosi e croccanti.', 'tax' => 4, 'unit' => 'kg'],
            ['code' => 'VER-005', 'category' => 'Verdura', 'name' => 'Insalata iceberg', 'description' => 'Insalata iceberg fresca, croccante e compatta.', 'tax' => 4, 'unit' => 'pz'],
            ['code' => 'VER-006', 'category' => 'Verdura', 'name' => 'Patate', 'description' => 'Patate versatili per frittura, forno e preparazioni professionali.', 'tax' => 4, 'unit' => 'kg'],
            ['code' => 'VER-007', 'category' => 'Verdura', 'name' => 'Cipolle dorate', 'description' => 'Cipolle dorate dal sapore equilibrato e adatte alla cottura.', 'tax' => 4, 'unit' => 'kg'],
            ['code' => 'VER-008', 'category' => 'Verdura', 'name' => 'Broccoli', 'description' => 'Broccoli freschi con cime compatte e colore uniforme.', 'tax' => 4, 'unit' => 'kg', 'seasonal' => true],
            ['code' => 'VER-009', 'category' => 'Verdura', 'name' => 'Finocchi', 'description' => 'Finocchi croccanti e profumati, ottimi crudi o cotti.', 'tax' => 4, 'unit' => 'kg', 'seasonal' => true],
            ['code' => 'VER-010', 'category' => 'Verdura', 'name' => 'Carote', 'description' => 'Carote fresche, croccanti e dalla colorazione uniforme.', 'tax' => 4, 'unit' => 'kg'],
            ['code' => 'CAM-001', 'category' => 'Prodotti campani', 'name' => 'Friarielli napoletani', 'description' => 'Friarielli napoletani dal caratteristico gusto amarognolo.', 'tax' => 4, 'unit' => 'kg', 'seasonal' => true],
            ['code' => 'CAM-002', 'category' => 'Prodotti campani', 'name' => 'Pomodorini del Piennolo', 'description' => 'Pomodorini del Piennolo dal sapore intenso e dalla polpa consistente.', 'tax' => 4, 'unit' => 'kg', 'seasonal' => true],
            ['code' => 'CAM-003', 'category' => 'Prodotti campani', 'name' => 'Limoni di Sorrento', 'description' => 'Limoni di Sorrento profumati, ricchi di succo e oli essenziali.', 'tax' => 4, 'unit' => 'kg', 'seasonal' => true],
            ['code' => 'CAM-004', 'category' => 'Prodotti campani', 'name' => 'Nocciole di Giffoni', 'description' => 'Nocciole di Giffoni sgusciate, aromatiche e croccanti.', 'tax' => 10, 'unit' => 'kg'],
            ['code' => 'CAM-005', 'category' => 'Prodotti campani', 'name' => 'Pasta di Gragnano', 'description' => 'Pasta di semola di grano duro prodotta secondo la tradizione di Gragnano.', 'tax' => 4, 'unit' => 'busta'],
            ['code' => 'CAM-006', 'category' => 'Prodotti campani', 'name' => 'Taralli napoletani', 'description' => 'Taralli napoletani friabili con mandorle e pepe.', 'tax' => 10, 'unit' => 'busta'],
            ['code' => 'CON-001', 'category' => 'Prodotti confezionati', 'name' => 'Passata di pomodoro', 'description' => 'Passata di pomodoro vellutata per sughi e preparazioni professionali.', 'tax' => 4, 'unit' => 'pz'],
            ['code' => 'CON-002', 'category' => 'Prodotti confezionati', 'name' => 'Pomodori pelati', 'description' => 'Pomodori pelati interi conservati nel proprio succo.', 'tax' => 4, 'unit' => 'pz'],
            ['code' => 'CON-003', 'category' => 'Prodotti confezionati', 'name' => 'Pasta di semola', 'description' => 'Pasta secca di semola di grano duro in formato ristorazione.', 'tax' => 4, 'unit' => 'busta'],
            ['code' => 'CON-004', 'category' => 'Prodotti confezionati', 'name' => 'Fagioli cannellini', 'description' => 'Fagioli cannellini confezionati, pronti per cucina e ristorazione.', 'tax' => 10, 'unit' => 'pz'],
            ['code' => 'CON-005', 'category' => 'Prodotti confezionati', 'name' => 'Olio extravergine di oliva', 'description' => 'Olio extravergine di oliva dal profilo equilibrato e versatile.', 'tax' => 4, 'unit' => 'pz'],
            ['code' => 'CON-006', 'category' => 'Prodotti confezionati', 'name' => 'Riso Carnaroli', 'description' => 'Riso Carnaroli adatto a risotti e preparazioni professionali.', 'tax' => 4, 'unit' => 'busta'],
        ];
    }

    private function prices(): array
    {
        return [
            'LAT-001' => 14.90,
            'LAT-002' => 9.50,
            'LAT-003' => 7.90,
            'LAT-004' => 9.90,
            'LAT-005' => 16.50,
            'LAT-006' => 12.90,
            'LAT-007' => 11.50,
            'LAT-008' => 12.50,
            'LAT-009' => 15.90,
            'LAT-010' => 16.90,
            'LAT-011' => 10.90,
            'LAT-012' => 24.90,
            'FRU-001' => 2.40,
            'FRU-002' => 2.90,
            'FRU-003' => 2.20,
            'FRU-004' => 3.50,
            'FRU-005' => 4.90,
            'FRU-006' => 2.20,
            'FRU-007' => 2.80,
            'FRU-008' => 3.90,
            'FRU-009' => 3.50,
            'FRU-010' => 4.50,
            'VER-001' => 2.90,
            'VER-002' => 2.60,
            'VER-003' => 2.70,
            'VER-004' => 3.90,
            'VER-005' => 2.20,
            'VER-006' => 1.80,
            'VER-007' => 1.70,
            'VER-008' => 2.90,
            'VER-009' => 2.40,
            'VER-010' => 1.90,
            'CAM-001' => 5.90,
            'CAM-002' => 7.90,
            'CAM-003' => 4.50,
            'CAM-004' => 18.90,
            'CAM-005' => 4.80,
            'CAM-006' => 12.90,
            'CON-001' => 2.30,
            'CON-002' => 2.50,
            'CON-003' => 2.20,
            'CON-004' => 3.20,
            'CON-005' => 12.90,
            'CON-006' => 4.20,
        ];
    }
}
