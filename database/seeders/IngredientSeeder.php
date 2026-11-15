<?php

namespace Database\Seeders;

use App\Enums\CategoryType;
use App\Enums\IngredientType;
use App\Enums\IngredientUnit;
use App\Models\Ingredient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class IngredientSeeder extends Seeder
{
    /**
     * Each entry is a recipe-facing category and the bottles a user could stock
     * under it. A category's unit is what a recipe asks for; a product's unit is
     * how its stock is measured, which is not always the same — bitters are
     * poured in dashes but bought in millilitres.
     *
     * @var list<array{
     *     name: string,
     *     category_type: CategoryType,
     *     unit: IngredientUnit,
     *     substitute_group: string|null,
     *     products: list<array{name: string, unit: IngredientUnit, bottle_size_ml: int|null}>
     * }>
     */
    private const array CATALOGUE = [
        [
            'name' => 'Gin',
            'category_type' => CategoryType::Spirit,
            'unit' => IngredientUnit::Millilitres,
            'substitute_group' => 'Gin (any)',
            'products' => [
                ['name' => 'Tanqueray London Dry', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 700],
                ['name' => 'Beefeater London Dry', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 700],
                ['name' => 'Sipsmith London Dry', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 700],
            ],
        ],
        [
            'name' => 'Vodka',
            'category_type' => CategoryType::Spirit,
            'unit' => IngredientUnit::Millilitres,
            'substitute_group' => 'Vodka (any)',
            'products' => [
                ['name' => 'Ketel One', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 700],
                ['name' => 'Absolut Blue', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 700],
            ],
        ],
        [
            'name' => 'White Rum',
            'category_type' => CategoryType::Spirit,
            'unit' => IngredientUnit::Millilitres,
            'substitute_group' => 'White Rum (any)',
            'products' => [
                ['name' => 'Havana Club 3 Anos', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 700],
                ['name' => 'Plantation 3 Stars', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 700],
            ],
        ],
        [
            'name' => 'Aged Rum',
            'category_type' => CategoryType::Spirit,
            'unit' => IngredientUnit::Millilitres,
            'substitute_group' => 'Aged Rum (any)',
            'products' => [
                ['name' => 'Appleton Estate Signature', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 700],
                ['name' => 'Mount Gay Black Barrel', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 700],
            ],
        ],
        [
            'name' => 'Bourbon',
            'category_type' => CategoryType::Spirit,
            'unit' => IngredientUnit::Millilitres,
            'substitute_group' => 'American Whiskey (any)',
            'products' => [
                ['name' => 'Buffalo Trace', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 750],
                ['name' => 'Four Roses Yellow Label', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 700],
            ],
        ],
        [
            'name' => 'Rye Whiskey',
            'category_type' => CategoryType::Spirit,
            'unit' => IngredientUnit::Millilitres,
            'substitute_group' => 'American Whiskey (any)',
            'products' => [
                ['name' => 'Rittenhouse Rye 100', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 750],
                ['name' => 'Sazerac Rye', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 750],
            ],
        ],
        [
            'name' => 'Blanco Tequila',
            'category_type' => CategoryType::Spirit,
            'unit' => IngredientUnit::Millilitres,
            'substitute_group' => 'Tequila (any)',
            'products' => [
                ['name' => 'Olmeca Altos Plata', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 700],
                ['name' => 'Tapatio Blanco', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 750],
            ],
        ],
        [
            'name' => 'Sweet Vermouth',
            'category_type' => CategoryType::Vermouth,
            'unit' => IngredientUnit::Millilitres,
            'substitute_group' => 'Sweet Vermouth (any)',
            'products' => [
                ['name' => 'Cocchi Storico Vermouth di Torino', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 750],
                ['name' => 'Carpano Antica Formula', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 1000],
                ['name' => 'Martini Rosso', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 750],
            ],
        ],
        [
            'name' => 'Dry Vermouth',
            'category_type' => CategoryType::Vermouth,
            'unit' => IngredientUnit::Millilitres,
            'substitute_group' => 'Dry Vermouth (any)',
            'products' => [
                ['name' => 'Dolin Dry', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 750],
                ['name' => 'Noilly Prat Original Dry', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 1000],
            ],
        ],
        [
            'name' => 'Bitter Aperitivo',
            'category_type' => CategoryType::Liqueur,
            'unit' => IngredientUnit::Millilitres,
            'substitute_group' => 'Campari-style Bitter',
            'products' => [
                ['name' => 'Campari', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 700],
                ['name' => 'Select Aperitivo', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 1000],
            ],
        ],
        [
            'name' => 'Orange Liqueur',
            'category_type' => CategoryType::Liqueur,
            'unit' => IngredientUnit::Millilitres,
            'substitute_group' => 'Orange Liqueur (any)',
            'products' => [
                ['name' => 'Cointreau', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 700],
                ['name' => 'Pierre Ferrand Dry Curacao', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 700],
            ],
        ],
        [
            'name' => 'Maraschino Liqueur',
            'category_type' => CategoryType::Liqueur,
            'unit' => IngredientUnit::Millilitres,
            'substitute_group' => null,
            'products' => [
                ['name' => 'Luxardo Maraschino', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 700],
            ],
        ],
        [
            'name' => 'Aromatic Bitters',
            'category_type' => CategoryType::Bitters,
            'unit' => IngredientUnit::Dash,
            'substitute_group' => 'Aromatic Bitters (any)',
            'products' => [
                ['name' => 'Angostura Aromatic Bitters', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 200],
                ['name' => "Peychaud's Bitters", 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 148],
            ],
        ],
        [
            'name' => 'Orange Bitters',
            'category_type' => CategoryType::Bitters,
            'unit' => IngredientUnit::Dash,
            'substitute_group' => null,
            'products' => [
                ['name' => "Regans' Orange Bitters No. 6", 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 148],
            ],
        ],
        [
            'name' => 'Lime Juice',
            'category_type' => CategoryType::Fresh,
            'unit' => IngredientUnit::Millilitres,
            'substitute_group' => null,
            'products' => [
                ['name' => 'Limes', 'unit' => IngredientUnit::Count, 'bottle_size_ml' => null],
            ],
        ],
        [
            'name' => 'Lemon Juice',
            'category_type' => CategoryType::Fresh,
            'unit' => IngredientUnit::Millilitres,
            'substitute_group' => null,
            'products' => [
                ['name' => 'Lemons', 'unit' => IngredientUnit::Count, 'bottle_size_ml' => null],
            ],
        ],
        [
            'name' => 'Mint',
            'category_type' => CategoryType::Fresh,
            'unit' => IngredientUnit::Count,
            'substitute_group' => null,
            'products' => [
                ['name' => 'Mint Sprigs', 'unit' => IngredientUnit::Count, 'bottle_size_ml' => null],
            ],
        ],
        [
            'name' => 'Simple Syrup',
            'category_type' => CategoryType::Syrup,
            'unit' => IngredientUnit::Millilitres,
            'substitute_group' => 'Sugar Syrup (any)',
            'products' => [
                ['name' => 'Simple Syrup 1:1', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 500],
                ['name' => 'Demerara Syrup 2:1', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 500],
            ],
        ],
        [
            'name' => 'Orgeat',
            'category_type' => CategoryType::Syrup,
            'unit' => IngredientUnit::Millilitres,
            'substitute_group' => null,
            'products' => [
                ['name' => 'Giffard Orgeat', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 700],
            ],
        ],
        [
            'name' => 'Soda Water',
            'category_type' => CategoryType::Mixer,
            'unit' => IngredientUnit::Millilitres,
            'substitute_group' => null,
            'products' => [
                ['name' => 'Fever-Tree Soda Water', 'unit' => IngredientUnit::Millilitres, 'bottle_size_ml' => 500],
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::CATALOGUE as $entry) {
            $category = Ingredient::updateOrCreate(
                ['slug' => Str::slug($entry['name'])],
                [
                    'parent_id' => null,
                    'type' => IngredientType::Category,
                    'category_type' => $entry['category_type'],
                    'name' => $entry['name'],
                    'unit' => $entry['unit'],
                    'substitute_group' => $entry['substitute_group'],
                    'bottle_size_ml' => null,
                ],
            );

            foreach ($entry['products'] as $product) {
                Ingredient::updateOrCreate(
                    ['slug' => Str::slug($product['name'])],
                    [
                        'parent_id' => $category->getKey(),
                        'type' => IngredientType::Product,
                        'category_type' => null,
                        'name' => $product['name'],
                        'unit' => $product['unit'],
                        'substitute_group' => null,
                        'bottle_size_ml' => $product['bottle_size_ml'],
                    ],
                );
            }
        }
    }
}
