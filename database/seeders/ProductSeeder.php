<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\UnitOfMeasure;
use App\Services\Pricing\ProductListPriceCalculator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $priceCalculator = app(ProductListPriceCalculator::class);
        $categories = ProductCategory::query()->pluck('id', 'name');
        $taxRateId = TaxRate::query()->where('percentage', 4)->value('id');
        $units = UnitOfMeasure::query()->pluck('id', 'symbol');

        foreach ($this->products() as $index => [$category, $name, $unit, $minimum]) {
            $purchaseCost = round(0.75 + (($index * 37) % 1600) / 100, 2);
            $priceMultiplier = $unit === 'g' ? 1000 / $minimum : 1;
            $canonicalUnit = $unit === 'g' ? 'kg' : $unit;
            $canonicalMinimum = $unit === 'g' ? $minimum / 1000 : $minimum;
            $purchaseCostPerUnit = round($purchaseCost * $priceMultiplier, 4);
            $markupPercentage = 100;
            $prices = $priceCalculator->calculate($purchaseCostPerUnit, $markupPercentage);
            $code = 'IPF-'.strtoupper(substr(sha1($name.'|'.$unit.'|'.$minimum), 0, 8));

            Product::query()->updateOrCreate(
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
                    'purchase_cost_per_unit' => $purchaseCostPerUnit,
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
