<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\UnitOfMeasure;
use App\Services\Pricing\ProductListPriceCalculator;
use App\Services\Pricing\PurchaseCostCalculator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProductCategorySeeder::class,
            TaxRateSeeder::class,
            UnitOfMeasureSeeder::class,
        ]);

        $priceCalculator = app(ProductListPriceCalculator::class);
        $purchaseCostCalculator = app(PurchaseCostCalculator::class);
        $categories = ProductCategory::query()->pluck('id', 'name');
        $taxRateId = TaxRate::query()->where('percentage', 4)->value('id');
        $taxRates = TaxRate::query()->get()->mapWithKeys(
            fn (TaxRate $taxRate): array => [(string) (float) $taxRate->percentage => $taxRate->id],
        );
        $units = UnitOfMeasure::query()->pluck('id', 'symbol');

        foreach ($this->products() as $index => [$category, $name, $unit, $minimum]) {
            $purchaseCostGross = round(0.75 + (($index * 37) % 1600) / 100, 2);
            $priceMultiplier = $unit === 'g' ? 1000 / $minimum : 1;
            $canonicalUnit = $unit === 'g' ? 'kg' : $unit;
            $canonicalMinimum = $unit === 'g' ? $minimum / 1000 : $minimum;
            $purchaseCostGrossPerUnit = round($purchaseCostGross * $priceMultiplier, 4);
            $purchaseCostNetPerUnit = $purchaseCostCalculator->netFromGross($purchaseCostGrossPerUnit, 4);
            $markupPercentage = 100;
            $prices = $priceCalculator->calculate($purchaseCostNetPerUnit, $markupPercentage);
            $code = 'IPF-'.strtoupper(substr(sha1($name.'|'.$unit.'|'.$minimum), 0, 8));

            Product::query()->firstOrCreate(
                ['code' => $code],
                [
                    'product_category_id' => $categories->get($category),
                    'tax_rate_id' => $taxRateId,
                    'default_unit_of_measure_id' => $units->get($canonicalUnit),
                    'name' => $name,
                    'slug' => Str::slug($name.'-'.$unit.'-'.$minimum),
                    'description' => $name.' selezionato per qualità e freschezza.',
                    'public_description' => $name.' selezionato per qualità e freschezza.',
                    'price_per_kg' => $prices['base_price'],
                    'purchase_cost_per_unit' => $purchaseCostNetPerUnit,
                    'purchase_cost_per_unit_gross' => $purchaseCostGrossPerUnit,
                    'markup_percentage' => $markupPercentage,
                    'base_price_per_unit' => $prices['base_price'],
                    'restaurant_price_per_unit' => $prices['restaurant_price'],
                    'base_minimum_quantity' => $canonicalMinimum,
                    'restaurant_minimum_quantity' => $canonicalMinimum * 5,
                    'is_public' => true,
                    'is_seasonal' => false,
                    'sort_order' => $index,
                    'active' => true,
                ],
            );
        }

        $this->seedPdfProducts($categories, $taxRates, $units, $priceCalculator, $purchaseCostCalculator);
    }

    private function seedPdfProducts(
        $categories,
        $taxRates,
        $units,
        ProductListPriceCalculator $priceCalculator,
        PurchaseCostCalculator $purchaseCostCalculator,
    ): void {
        foreach ($this->pdfProducts() as $index => $row) {
            [$category, $name, $unit, $purchaseGross, $taxPercentage, $websitePrice, $minimum, $package, $aliases] = $row;
            $purchaseNet = $purchaseCostCalculator->netFromGross($purchaseGross, $taxPercentage);
            $markup = $priceCalculator->markupFromPrice($purchaseNet, $websitePrice);
            $active = $purchaseGross !== null && $websitePrice !== null;
            $code = 'IPF-PDF-'.strtoupper(substr(sha1($name.'|'.$unit.'|'.$minimum), 0, 8));
            $description = $package === null ? $name.'.' : $name.'. '.$package.'.';

            $product = Product::withTrashed()->where('code', $code)->first();

            if (! $product && $aliases !== []) {
                $product = Product::withTrashed()->whereIn('name', [$name, ...$aliases])->orderBy('id')->first();
            }

            $attributes = [
                'product_category_id' => $categories->get($category),
                'tax_rate_id' => $taxRates->get((string) (float) $taxPercentage),
                'default_unit_of_measure_id' => $units->get($unit),
                'name' => $name,
                'slug' => Str::slug($name.'-'.$unit.'-'.$minimum),
                'description' => $description,
                'public_description' => $description,
                'price_per_kg' => $websitePrice ?? 0,
                'purchase_cost_per_unit' => $purchaseNet,
                'purchase_cost_per_unit_gross' => $purchaseGross ?? 0,
                'markup_percentage' => $markup,
                'restaurant_markup_percentage' => $markup,
                'base_price_per_unit' => $websitePrice ?? 0,
                'restaurant_price_per_unit' => $websitePrice ?? 0,
                'base_minimum_quantity' => $minimum,
                'restaurant_minimum_quantity' => $minimum * 5,
                'is_public' => $active,
                'is_seasonal' => false,
                'sort_order' => 1000 + $index,
                'notes' => $package,
                'active' => $active,
            ];

            if ($product) {
                $product->restore();
                $product->fill($attributes)->save();
            } else {
                $product = Product::query()->create(['code' => $code, ...$attributes]);
            }

            $product->update([
                'base_price_per_unit' => $websitePrice ?? 0,
                'restaurant_price_per_unit' => $websitePrice ?? 0,
            ]);
        }
    }

    private function pdfProducts(): array
    {
        return [
            ['Prodotti campani', 'Olive verdi', 'kg', 6.60, 10, 9.00, 1, null, []],
            ['Prodotti campani', 'Olive nere', 'kg', 6.90, 10, 9.00, 1, null, []],
            ['Prodotti campani', 'Olive condite', 'kg', 6.90, 10, 10.00, 1, null, []],
            ['Prodotti campani', 'Salame Napoli', 'kg', 10.50, 10, 15.00, 1, null, []],
            ['Prodotti campani', 'Ciampa dolce', 'kg', 10.50, 10, 15.00, 1, null, []],
            ['Prodotti campani', 'Ciampa piccante', 'kg', 10.50, 10, 15.00, 1, null, []],
            ['Prodotti campani', 'Salsiccia punta di coltello', 'kg', 7.50, 10, 10.00, 1, null, []],
            ['Prodotti campani', 'Salsiccia cervellatina', 'kg', 7.50, 10, 10.00, 1, null, []],
            ['Prodotti campani', 'Costolette di maiale', 'kg', 7.00, 10, 10.00, 1, null, []],
            ['Prodotti campani', 'Fette di arrosto', 'kg', 15.00, 10, 18.80, 1, null, []],
            ['Prodotti campani', 'Friselle bianche e integrali', 'pz', 1.20, 4, 2.00, 1, null, []],
            ['Prodotti campani', 'Gnocchi di patate', 'kg', 1.20, 10, 3.00, 1, null, []],
            ['Prodotti campani', 'Taralli Extra Napoli', 'pz', 3.88, 4, 5.00, 1, null, []],
            ['Prodotti campani', 'Passata di pomodoro', 'pz', 0.91, 4, 1.70, 1, null, []],
            ['Prodotti campani', "Melanzane sott'olio", 'pz', 4.17, 10, 6.00, 1, null, []],
            ['Prodotti campani', "Scarole sott'olio", 'pz', 4.34, 10, 6.00, 1, null, []],
            ['Prodotti campani', "Friarielli sott'olio", 'pz', 4.00, 10, 6.00, 1, null, []],
            ['Prodotti campani', 'Saltimbocca', 'pz', 1.30, 10, 2.50, 1, null, []],
            ['Prodotti campani', 'Grano cotto', 'pz', 1.29, 10, 2.00, 1, null, []],
            ['Prodotti campani', 'Fiale millefiori', 'pz', 0.20, 22, 0.50, 1, null, []],
            ['Prodotti campani', 'Vino Aglianico', 'pz', 1.42, 22, 2.05, 1, null, []],
            ['Prodotti campani', 'Vino Falanghina', 'pz', 1.42, 22, 2.05, 1, null, []],
            ['Prodotti campani', 'Pane cafone', 'pz', 1.25, 4, 2.00, 1, null, []],
            ['Prodotti campani', 'Pane misto cafone', 'pz', 1.25, 4, 2.50, 1, null, []],
            ['Prodotti campani', 'Taralli Covo', 'pz', 2.08, 4, 3.00, 1, null, []],
            ['Prodotti campani', 'Lupini', 'pz', 2.33, 10, 2.80, 1, null, []],
            ['Prodotti campani', 'Uova', 'pz', 0.24, 4, 0.35, 1, null, []],
            ['Prodotti campani', 'Noci', 'kg', 5.00, 4, 7.50, 1, null, ['Noci']],
            ['Prodotti campani', 'Noccioline', 'kg', 8.40, 10, 10.90, 1, null, ['Noccioline Tostate']],
            ['Prodotti campani', 'Pistacchi', 'kg', 14.00, 10, 16.50, 1, null, ['Pistacchi']],
            ['Prodotti campani', 'Spagnolette', 'kg', 4.50, 10, 7.00, 1, null, []],
            ['Prodotti campani', 'Soffritto', 'pz', 3.05, 10, 4.00, 1, null, []],
            ['Prodotti campani', 'Carciofi arrostiti', 'conf', 5.50, 10, 7.00, 1, 'Confezione da 5 pezzi', []],
            ['Prodotti campani', 'Panini napoletani ripieni', 'conf', null, 10, null, 1, 'Confezione da 5 pezzi; prezzi non indicati nel PDF', []],
            ['Prodotti campani', 'Trippa napoletana', 'kg', 7.50, 10, 10.00, 1, null, []],
            ['Prodotti campani', 'Preparato misto casatiello', 'kg', 10.00, 10, 15.00, 1, null, []],

            ['Dolci campani', 'Babà', 'conf', 2.40, 10, 6.00, 1, 'Confezione da 3 pezzi', []],
            ['Dolci campani', 'Babà crema', 'conf', 2.70, 10, 6.00, 1, 'Confezione da 3 pezzi', []],
            ['Dolci campani', 'Sfogliatelle frolle', 'conf', 2.40, 10, 6.00, 1, 'Confezione da 3 pezzi', []],
            ['Dolci campani', 'Sfogliatelle ricce', 'conf', 2.40, 10, 6.00, 1, 'Confezione da 3 pezzi', []],
            ['Dolci campani', 'Cannoli', 'conf', 3.00, 10, 6.00, 1, 'Confezione da 3 pezzi', []],
            ['Dolci campani', 'Zeppole San Giuseppe', 'conf', 3.60, 10, 6.00, 1, 'Confezione da 3 pezzi', []],
            ['Dolci campani', 'Pastiera', 'conf', 10.00, 10, 15.00, 1, 'Confezione da 1 pezzo', []],
            ['Dolci campani', 'Diplomatica', 'conf', 2.40, 10, 6.00, 1, 'Confezione da 3 pezzi', []],
            ['Dolci campani', 'Piccola pasticceria', 'conf', 4.50, 10, 9.00, 1, 'Confezione da 500 g', []],
            ['Dolci campani', 'Codine al cioccolato', 'conf', 4.50, 10, 9.00, 1, 'Confezione da 500 g', []],
            ['Dolci campani', 'Biscotti amarena', 'conf', 2.40, 10, 6.00, 1, 'Confezione da 3 pezzi', []],

            ['Latticini', 'Mozzarella da 125 g', 'kg', 9.17, 4, 11.50, 0.125, 'Formato da 125 g', ['Mozzarella di bufala campana']],
            ['Latticini', 'Mozzarella a bocconcini', 'kg', 9.17, 4, 11.50, 1, null, []],
            ['Latticini', 'Mozzarella da 500 g', 'kg', 9.17, 4, 11.50, 0.5, 'Formato da 500 g', []],
            ['Latticini', 'Provola affumicata da 500 g', 'kg', 9.50, 4, 11.50, 0.5, 'Formato da 500 g', ['Provola affumicata']],
            ['Latticini', 'Ricotta di bufala', 'kg', 3.00, 4, 6.00, 1, null, ['Ricotta di bufala']],
            ['Latticini', 'Burrata di vaccina', 'kg', 8.70, 4, 11.50, 1, null, ['Burrata']],
            ['Latticini', 'Stracciatella di vaccina', 'kg', 8.70, 4, 11.50, 1, null, ['Stracciatella']],
            ['Latticini', 'Fior di latte Agerola', 'kg', 8.20, 4, 11.50, 1, null, ['Fior di latte']],
            ['Latticini', 'Mozzarella senza lattosio', 'kg', 10.00, 4, 15.00, 1, null, []],
            ['Latticini', 'Caciocavallo bianco o affumicato', 'kg', 8.90, 4, 11.50, 1, null, ['Caciocavallo']],
            ['Latticini', 'Auricchio semipiccante', 'kg', 11.00, 4, 15.00, 1, null, []],
            ['Latticini', 'Ricotta dura bianca', 'kg', 9.50, 4, 15.00, 1, null, []],
            ['Latticini', 'Ricotta dura piccante', 'kg', 9.50, 4, 15.00, 1, null, []],

            ['Prodotti pizzerie', 'Provola fior di latte', 'kg', 6.50, 4, 8.50, 1, null, []],
            ['Prodotti pizzerie', 'Fior di latte intero', 'kg', 5.80, 4, 7.80, 1, null, []],
            ['Prodotti pizzerie', 'Fior di latte Julienne', 'kg', 6.00, 4, 8.00, 1, null, []],

            ['Materiali di consumo', 'Cartellini prezzari', 'pz', 2.00, 22, 3.50, 1, null, []],
            ['Materiali di consumo', 'Cestini da 1 kg', 'cartone', 53.00, 22, 65.00, 1, 'Cartone di cestini da 1 kg', []],
            ['Materiali di consumo', 'Cestini da 0,5 kg', 'fila', 3.50, 22, 7.00, 1, 'Una fila di cestini da 0,5 kg', []],
            ['Materiali di consumo', 'Sacchetti grandi', 'cartone', 35.00, 22, 45.00, 1, null, []],
            ['Materiali di consumo', 'Sacchetti piccoli', 'cartone', 16.00, 22, 30.00, 1, null, []],
            ['Materiali di consumo', 'Rotoloni asciugatutto', 'coppia', 2.50, 22, 5.00, 1, null, []],
            ['Materiali di consumo', 'Cartoni per le pizze', 'cartone', null, 22, null, 1, 'Prezzi non indicati nel PDF', []],
        ];
    }

    private function products(): array
    {
        $dairy = array_map(fn (string $name): array => ['Latticini', $name, 'kg', 1], [
            'Mozzarella di bufala campana', 'Fior di latte', 'Ricotta vaccina', 'Ricotta di bufala',
            'Caciocavallo', 'Provola affumicata', 'Scamorza bianca', 'Scamorza affumicata',
            'Burrata', 'Stracciatella', 'Mascarpone', 'Parmigiano Reggiano',
        ]);

        return [...$dairy, ...$this->vegetables(), ...$this->fruit(), ...$this->packaged()];
    }

    private function vegetables(): array
    {
        return $this->rows('Verdura', [
            ['Aglio confezione', 'g', 200], ["Treccia d'Aglio", 'kg', 1], ['Cipolle Dorate', 'kg', 5],
            ['Cipolle Rosse', 'kg', 5], ['Cipolle Bianche', 'kg', 5], ['Cipolla di Tropea', 'kg', 1],
            ['Scalogno', 'g', 250], ['Porri', 'kg', 1], ['Patate Gialle 1,5 kg', 'kg', 1.5],
            ['Patate Gialle 5 kg', 'kg', 5], ['Patate Rosse', 'kg', 5], ['Patate Viola', 'kg', 5],
            ['Patate Dolci', 'cassa', 1], ['Asparagi', 'kg', 1], ['Barbabietole Rosse', 'pz', 1],
            ['Broccoletti', 'kg', 1], ['Carciofi', 'pz', 1], ['Carciofi con le Spine', 'pz', 1],
            ['Carote confezione', 'kg', 1], ['Carote sfuse', 'cassa', 1], ['Catalogna', 'kg', 1],
            ['Cavolfiore Bianco', 'kg', 1], ['Cavoli di Bruxelles', 'pz', 1], ['Cavolo Verde', 'kg', 1],
            ['Crauto Rosso', 'kg', 1], ['Crauto Bianco', 'kg', 1], ['Cavolo Verza', 'kg', 1],
            ['Cime di Rapa', 'kg', 1], ['Erbette', 'cassa', 1], ['Fagiolini', 'kg', 1],
            ['Fave Fresche', 'kg', 1], ['Fiori di Zucca', 'vaschetta', 1], ['Fiori di Zucca Freschi', 'kg', 1],
            ['Funghi Champignons', 'kg', 1], ['Zucca Mantovana', 'kg', 1], ['Zucca Napoli', 'kg', 1],
            ['Sedano', 'kg', 1], ['Melanzane', 'kg', 1], ['Zucchine', 'kg', 1], ['Finocchi', 'kg', 1],
            ['Cetrioli', 'kg', 1], ['Peperoni', 'kg', 1], ['Pomodori Ramato', 'kg', 1],
            ['Pomodori Ciliegino', 'kg', 1], ['Pomodoro Ciliegino Giallo', 'kg', 1],
            ['Datterino Rosso', 'kg', 1], ['Datterino Giallo', 'kg', 1], ['Pomodoro Cuore di Bue', 'kg', 1],
            ['Pomodoro Sardo', 'kg', 1], ['Pomodori Piccadilly', 'kg', 1], ['Insalata Iceberg', 'cassa', 1],
            ['Insalata Lattuga', 'cassa', 1], ['Insalata Gentile', 'cassa', 1], ['Radicchio Rosso', 'kg', 1],
            ['Scarola', 'kg', 1], ['Scarola Riccia', 'kg', 1], ['Carote Julienne', 'pz', 1],
        ]);
    }

    private function fruit(): array
    {
        return $this->rows('Frutta', [
            ['Ciliegie', 'kg', 1], ['Fragole', 'kg', 1], ['Albicocche', 'kg', 1], ['Pesche', 'kg', 1],
            ['Pesche Tabacchiere', 'kg', 1], ['Pesche Noci', 'kg', 1], ['Percoche', 'kg', 1],
            ['Prugne Rosse', 'kg', 1], ['Prugne Gialle', 'kg', 1], ['Fichi', 'kg', 1],
            ["Fichi d'India", 'kg', 1], ['Nespole', 'kg', 1], ['Mele Fuji', 'kg', 1],
            ['Mele Melinda', 'kg', 1], ['Mele Annurche', 'kg', 1], ['Mele Red Delicious', 'kg', 1],
            ['Mele Pink Lady', 'kg', 1], ['Pere Abate', 'kg', 1], ['Pere Williams', 'kg', 1],
            ['Pere Coscia', 'kg', 1], ['Kiwi', 'kg', 1], ['Cachi', 'kg', 1], ['Cachi Mela', 'kg', 1],
            ['Cachi Maturi', 'kg', 1], ['Meloni Retati', 'kg', 1], ['Meloni Gialletti', 'kg', 1],
            ['Angurie', 'kg', 1], ['Angurie Baby', 'kg', 1], ['Castagne', 'kg', 1], ['Melograni', 'pz', 1],
            ['Limoni Trattati', 'kg', 1], ['Limoni Naturali', 'kg', 1], ['Mandarini', 'kg', 1],
            ['Clementini', 'kg', 1], ['Arancia Tarocco', 'kg', 1], ['Arancia Navel', 'kg', 1],
            ['Pompelmo', 'kg', 1], ['Lime', 'cassa', 1], ['Uva Italia', 'kg', 1],
            ['Uva Senza Semi', 'kg', 1], ['Uva Nera', 'kg', 1], ['Uva Rosé', 'kg', 1],
            ['Mirtilli', 'vaschetta', 1], ['Lamponi', 'vaschetta', 1], ['More', 'vaschetta', 1],
            ['Ribes', 'vaschetta', 1], ['Ananas', 'pz', 1], ['Cocco', 'pz', 1], ['Mango', 'pz', 1],
            ['Mango Via Aerea', 'pz', 1], ['Avocado', 'pz', 1], ['Litchi', 'kg', 1],
        ]);
    }

    private function packaged(): array
    {
        return $this->rows('Prodotti confezionati', [
            ['Rucola', 'g', 100], ['Valeriana', 'g', 100], ['Misticanza', 'g', 100],
            ['Novella', 'g', 100], ['Tenero Mix', 'g', 100], ['Insalata Rucola', 'g', 250],
            ['Insalata Mista', 'g', 500], ['Spinaci', 'g', 500], ['Lenticchie Secche', 'busta', 1],
            ['Fagioli Bianchi Secchi', 'busta', 1], ['Fagioli Rossi Secchi', 'busta', 1], ['Ceci Secchi', 'busta', 1],
            ['Basilico 1 kg', 'kg', 1], ['Basilico 500 g', 'g', 500], ['Basilico 30 g', 'g', 30],
            ['Prezzemolo', 'kg', 1], ['Menta 500 g', 'g', 500], ['Menta 30 g', 'g', 30],
            ['Rosmarino 500 g', 'g', 500], ['Rosmarino 30 g', 'g', 30], ['Alloro', 'g', 30],
            ['Salvia', 'g', 30], ['Origano', 'g', 50], ['Timo', 'g', 30], ['Aromi Misti', 'g', 500],
            ['Zenzero', 'kg', 5], ['Noccioline Tostate', 'g', 500], ['Pistacchi', 'g', 250],
            ['Mandorle', 'g', 250], ['Arachidi Tostate', 'g', 500], ['Noci', 'g', 500], ['Pinoli', 'kg', 1],
        ]);
    }

    private function rows(string $category, array $rows): array
    {
        return array_map(fn (array $row): array => [$category, ...$row], $rows);
    }
}
