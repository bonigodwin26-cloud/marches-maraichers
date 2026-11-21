<?php

namespace Database\Seeders;

use App\Models\Market;
use App\Models\PriceObservation;
use App\Models\Producer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Données de démonstration pour une présentation client.
 *
 * Jamais appelée par DatabaseSeeder : les données de production restent saisies
 * et validées par l'équipe. Les relevés créés ici portent tous la mention [DEMO].
 */
class PresentationSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production') && (! $this->command || ! $this->command->option('force'))) {
            throw new RuntimeException('Données de démonstration refusées en production sans --force.');
        }

        // Les photos sont fournies dans public/images ; le site et l'API mobile
        // les servent via le disque « public » (/storage), il faut donc les y publier.
        $this->publishDemoImages();

        $collector = User::firstOrCreate(
            ['email' => 'collecte-demonstration@marches-maraichers.local'],
            [
                'name' => 'Équipe collecte (démo)',
                'password' => Hash::make(Str::password(40)),
                'email_verified_at' => now(),
                'privacy_accepted_at' => now(),
                'privacy_policy_version' => config('privacy.version'),
            ],
        );

        $products = [
            ['Tomate', 'Légumes', 'kg', 'tomate.jpg'],
            ['Oignon', 'Légumes', 'kg', 'oignon.jpg'],
            ['Piment', 'Légumes', 'kg', 'piment.jpg'],
            ['Gombo', 'Légumes', 'kg', 'gombo.jpg'],
            ['Aubergine', 'Légumes', 'kg', 'aubergine-locale.jpg'],
            ['Légume à feuille', 'Légumes', 'botte', 'legume-feuille.jpg'],
            ['Haricot vert', 'Légumes', 'kg', 'legume-feuille.jpg'],
            ['Carotte', 'Légumes', 'kg', 'legume-feuille.jpg'],
        ];

        $productModels = [];
        foreach ($products as [$name, $category, $unit, $photo]) {
            $productModels[$name] = Product::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'category' => $category,
                    'unit' => $unit,
                    'image_path' => 'products/'.$photo,
                    'is_active' => true,
                ],
            );
        }

        $markets = [
            ['Marché Dantokpa', 'Cotonou', 'Littoral', 6.3655, 2.4100, 'Lun–Sam · 06:00–18:00', '+229012345678', '01-market-seattle.jpg'],
            ['Marché de Mokola', 'Cotonou', 'Littoral', 6.3560, 2.4180, 'Lun–Sam · 07:00–17:00', '+229012345679', '02-market-colourful.jpg'],
            ['Marché de Porto-Novo', 'Porto-Novo', 'Ouémé', 6.4969, 2.6032, 'Mar–Dim · 06:30–17:30', '+229012345680', '03-delhi-produce.jpg'],
            ['Marché de Bohicon', 'Bohicon', 'Zou', 7.2017, 2.0717, 'Mer–Sam · 07:00–16:00', '+229012345681', '04-vegetable-field.jpg'],
            ['Marché d’Abomey-Calavi', 'Abomey-Calavi', 'Atlantique', 6.4483, 2.3567, 'Lun–Ven · 07:00–17:00', '+229012345682', '05-farmers-baskets.jpg'],
        ];

        $marketModels = [];
        foreach ($markets as [$name, $commune, $department, $latitude, $longitude, $hours, $phone, $photo]) {
            $marketModels[$name] = Market::updateOrCreate(
                ['name' => $name, 'commune' => $commune],
                [
                    'department' => $department,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'opening_hours' => $hours,
                    'contact_phone' => $phone,
                    'image_path' => 'markets/'.$photo,
                    'is_active' => true,
                ],
            );
        }

        $producers = [
            ['Coopérative des maraîchers de Dantokpa', 'Marché Dantokpa', '+229012345690'],
            ['Groupement des producteurs de Mokola', 'Marché de Mokola', '+229012345691'],
            ['Union paysanne de l’Ouémé', 'Marché de Porto-Novo', '+229012345692'],
        ];

        foreach ($producers as [$name, $marketName, $phone]) {
            $market = self::demoMarket($marketModels, $marketName);

            Producer::updateOrCreate(
                ['name' => $name],
                [
                    'organization' => 'Groupement de producteurs (démonstration)',
                    'phone' => $phone,
                    'market_id' => $market->id,
                    'commune' => $market->commune,
                    'notes' => '[DEMO] Producteur fictif pour la présentation.',
                    'notification_consent' => false,
                    'is_active' => true,
                ],
            );
        }

        $observations = [
            ['Tomate', 'Marché Dantokpa', 450, 'Bonne', 120],
            ['Oignon', 'Marché Dantokpa', 320, 'Standard', 200],
            ['Piment', 'Marché de Mokola', 900, 'Bonne', 45],
            ['Gombo', 'Marché de Mokola', 650, 'Standard', 60],
            ['Aubergine', 'Marché de Porto-Novo', 500, 'Bonne', 80],
            ['Légume à feuille', 'Marché de Porto-Novo', 250, 'Fraîche', 150],
            ['Tomate', 'Marché de Bohicon', 400, 'Standard', 90],
            ['Haricot vert', 'Marché de Bohicon', 700, 'Bonne', 55],
            ['Carotte', 'Marché d’Abomey-Calavi', 480, 'Standard', 75],
            ['Oignon', 'Marché d’Abomey-Calavi', 300, 'Bonne', 180],
        ];

        foreach ($observations as $index => [$productName, $marketName, $price, $quality, $quantity]) {
            $product = self::demoProduct($productModels, $productName);
            $market = self::demoMarket($marketModels, $marketName);
            $note = sprintf('[DEMO] Exemple de relevé n°%d pour la présentation.', $index + 1);

            PriceObservation::firstOrCreate(
                ['notes' => $note],
                [
                    'product_id' => $product->id,
                    'market_id' => $market->id,
                    'collected_by' => $collector->id,
                    'price_xof' => $price,
                    'unit_snapshot' => $product->unit,
                    'quality' => $quality,
                    'available_quantity' => $quantity,
                    'observed_at' => now()->subHours(2 + $index),
                    'status' => 'published',
                ],
            );
        }
    }

    /**
     * Copie les photos de démonstration vers le disque public afin que
     * Storage::disk('public')->url() produise des URLs valides partout.
     */
    private function publishDemoImages(): void
    {
        $groups = [
            'products' => ['tomate.jpg', 'oignon.jpg', 'piment.jpg', 'gombo.jpg', 'aubergine-locale.jpg', 'legume-feuille.jpg'],
            'markets' => ['01-market-seattle.jpg', '02-market-colourful.jpg', '03-delhi-produce.jpg', '04-vegetable-field.jpg', '05-farmers-baskets.jpg'],
        ];

        foreach ($groups as $folder => $files) {
            foreach ($files as $file) {
                $source = public_path('images/'.$folder.'/'.$file);
                $contents = is_file($source) ? file_get_contents($source) : false;
                if ($contents === false) {
                    continue;
                }

                Storage::disk('public')->put($folder.'/'.$file, $contents);
            }
        }
    }

    /** @param array<string, Market|null> $markets */
    private static function demoMarket(array $markets, string $name): Market
    {
        $market = $markets[$name] ?? null;
        if (! $market instanceof Market) {
            throw new RuntimeException('Référence de marché de démonstration introuvable : '.$name);
        }

        return $market;
    }

    /** @param array<string, Product|null> $products */
    private static function demoProduct(array $products, string $name): Product
    {
        $product = $products[$name] ?? null;
        if (! $product instanceof Product) {
            throw new RuntimeException('Référence de produit de démonstration introuvable : '.$name);
        }

        return $product;
    }
}
