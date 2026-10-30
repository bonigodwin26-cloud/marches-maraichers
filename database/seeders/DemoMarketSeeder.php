<?php

namespace Database\Seeders;

use App\Models\Market;
use App\Models\PriceObservation;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoMarketSeeder extends Seeder
{
    public function run(): void
    {
        $collector = User::query()->first();

        if (! $collector) {
            $collector = User::query()->create([
                'name' => 'Collecteur fictif (démo)',
                'email' => 'collecteur-demo@example.test',
                'password' => Hash::make(Str::random(64)),
            ]);
        }

        $markets = collect([
            ['name' => 'Marché Dantokpa', 'commune' => 'Cotonou', 'department' => 'Littoral'],
            ['name' => 'Marché Ouando', 'commune' => 'Porto-Novo', 'department' => 'Ouémé'],
            ['name' => 'Marché de Bohicon', 'commune' => 'Bohicon', 'department' => 'Zou'],
            ['name' => 'Marché Arzèkè', 'commune' => 'Parakou', 'department' => 'Borgou'],
        ])->mapWithKeys(fn (array $market) => [
            $market['commune'] => Market::query()->firstOrCreate($market, ['is_active' => true]),
        ]);

        $products = collect([
            ['name' => 'Tomate', 'category' => 'Légume-fruit', 'unit' => 'Cagette de 25 kg', 'slug' => 'tomate-demo'],
            ['name' => 'Oignon', 'category' => 'Bulbe', 'unit' => 'Sac de 25 kg', 'slug' => 'oignon-demo'],
            ['name' => 'Piment', 'category' => 'Légume-fruit', 'unit' => 'Kilogramme', 'slug' => 'piment-demo'],
            ['name' => 'Gombo', 'category' => 'Légume-fruit', 'unit' => 'Kilogramme', 'slug' => 'gombo-demo'],
            ['name' => 'Aubergine locale', 'category' => 'Légume-fruit', 'unit' => 'Kilogramme', 'slug' => 'aubergine-locale-demo'],
            ['name' => 'Légume-feuille', 'category' => 'Feuilles', 'unit' => 'Botte', 'slug' => 'legume-feuille-demo'],
        ])->mapWithKeys(fn (array $product) => [
            $product['slug'] => Product::query()->firstOrCreate(['slug' => $product['slug']], $product + ['is_active' => true]),
        ]);

        PriceObservation::query()->where('notes', 'like', '[DEMO]%')->delete();

        $basePrices = [
            'tomate-demo' => [15500, 14800, 13200, 14000],
            'oignon-demo' => [22500, 21800, 20500, 21200],
            'piment-demo' => [1200, 1100, 950, 1000],
            'gombo-demo' => [900, 850, 750, 800],
            'aubergine-locale-demo' => [800, 750, 650, 700],
            'legume-feuille-demo' => [350, 300, 250, 300],
        ];
        $marketOrder = ['Cotonou', 'Porto-Novo', 'Bohicon', 'Parakou'];

        foreach ($products as $slug => $product) {
            foreach ($marketOrder as $index => $commune) {
                $price = $basePrices[$slug][$index];
                foreach ([2, 1, 0] as $dayOffset => $position) {
                    $variation = [0.94, 1, 1.06][$position];
                    PriceObservation::query()->create([
                        'product_id' => $product->id,
                        'market_id' => $markets[$commune]->id,
                        'collected_by' => $collector->id,
                        'price_xof' => (int) (round(($price * $variation) / 50) * 50),
                        'unit_snapshot' => $product->unit,
                        'quality' => ['Qualité courante (exemple)', 'Qualité standard (exemple)', 'Qualité supérieure (exemple)'][$position],
                        'observed_at' => now()->subDays($dayOffset)->setTime(9 + $position, 0),
                        'status' => 'published',
                        'notes' => '[DEMO] Prix simulé à titre illustratif — ne correspond pas à une collecte terrain.',
                    ]);
                }
            }
        }
    }
}
