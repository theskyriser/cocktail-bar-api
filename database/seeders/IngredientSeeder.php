<?php

namespace Database\Seeders;

use App\Enums\IngredientKind;
use App\Enums\IngredientType;
use App\Enums\MeasurementUnit;
use App\Enums\SubstituteQuality;
use App\Models\Ingredient;
use Illuminate\Database\Seeder;

class IngredientSeeder extends Seeder
{
    /**
     * The curated tree, keyed on slug so a second run updates the row it
     * already created. Curated rows carry a null creator, which is what the
     * partial unique indexes on `slug` and `path` are scoped to.
     */
    public function run(): void
    {
        /** @var array<string, Ingredient> $bySlug */
        $bySlug = [];

        foreach ($this->categories() as $category) {
            $parent = $this->upsert($category, IngredientKind::Category, null);
            $bySlug[$parent->slug] = $parent;

            foreach ($category['products'] as $product) {
                $child = $this->upsert([
                    'type' => $category['type'],
                    'is_alcoholic' => $category['is_alcoholic'],
                    'default_unit' => $category['default_unit'],
                    ...$product,
                ], IngredientKind::Product, $parent);

                $bySlug[$child->slug] = $child;
            }
        }

        $this->linkSubstitutes($bySlug);
    }

    /**
     * A recipe line against a category is already satisfied by any product
     * beneath it, so only a stand-in that crosses categories needs a row here.
     *
     * @param  array<string, Ingredient>  $bySlug
     */
    private function linkSubstitutes(array $bySlug): void
    {
        foreach ($this->substitutions() as [$from, $to, $quality, $note]) {
            if (! isset($bySlug[$from], $bySlug[$to])) {
                continue;
            }

            $bySlug[$from]->substitutes()->syncWithoutDetaching([
                $bySlug[$to]->id => ['quality' => $quality, 'note' => $note],
            ]);
        }
    }

    /**
     * @param  array{slug: string, name: string, type: IngredientType, is_alcoholic: bool, default_unit: MeasurementUnit, aliases?: array<int, string>}  $attributes
     */
    private function upsert(array $attributes, IngredientKind $kind, ?Ingredient $parent): Ingredient
    {
        $ingredient = Ingredient::firstOrNew([
            'slug' => $attributes['slug'],
            'created_by_user_id' => null,
        ]);

        $ingredient->fill([
            'name' => $attributes['name'],
            'kind' => $kind,
            'type' => $attributes['type'],
            'is_alcoholic' => $attributes['is_alcoholic'],
            'default_unit' => $attributes['default_unit'],
        ]);

        $ingredient->parent()->associate($parent);
        $ingredient->save();

        foreach ($attributes['aliases'] ?? [] as $alias) {
            $ingredient->aliases()->firstOrCreate(['alias' => $alias]);
        }

        return $ingredient;
    }

    /**
     * Products inherit their category's type, strength and unit unless they
     * name their own.
     *
     * @return array<int, array{slug: string, name: string, type: IngredientType, is_alcoholic: bool, default_unit: MeasurementUnit, aliases?: array<int, string>, products: array<int, array{slug: string, name: string, aliases?: array<int, string>, type?: IngredientType, is_alcoholic?: bool, default_unit?: MeasurementUnit}>}>
     */
    private function categories(): array
    {
        return [
            [
                'slug' => 'gin',
                'name' => 'Gin',
                'type' => IngredientType::Spirit,
                'is_alcoholic' => true,
                'default_unit' => MeasurementUnit::Millilitre,
                'products' => [
                    ['slug' => 'london-dry-gin', 'name' => 'London Dry Gin', 'aliases' => ['london dry', 'dry gin']],
                    ['slug' => 'plymouth-gin', 'name' => 'Plymouth Gin', 'aliases' => ['plymouth']],
                ],
            ],
            [
                'slug' => 'whiskey',
                'name' => 'Whiskey',
                'type' => IngredientType::Spirit,
                'is_alcoholic' => true,
                'default_unit' => MeasurementUnit::Millilitre,
                'aliases' => ['whisky'],
                'products' => [
                    ['slug' => 'bourbon', 'name' => 'Bourbon', 'aliases' => ['bourbon whiskey']],
                    ['slug' => 'rye', 'name' => 'Rye', 'aliases' => ['rye whiskey']],
                    ['slug' => 'scotch', 'name' => 'Scotch', 'aliases' => ['scotch whisky']],
                ],
            ],
            [
                'slug' => 'rum',
                'name' => 'Rum',
                'type' => IngredientType::Spirit,
                'is_alcoholic' => true,
                'default_unit' => MeasurementUnit::Millilitre,
                'products' => [
                    ['slug' => 'white-rum', 'name' => 'White Rum', 'aliases' => ['light rum', 'silver rum']],
                    ['slug' => 'dark-rum', 'name' => 'Dark Rum', 'aliases' => ['aged rum']],
                ],
            ],
            [
                'slug' => 'tequila',
                'name' => 'Tequila',
                'type' => IngredientType::Spirit,
                'is_alcoholic' => true,
                'default_unit' => MeasurementUnit::Millilitre,
                'products' => [
                    ['slug' => 'blanco-tequila', 'name' => 'Blanco Tequila', 'aliases' => ['silver tequila', 'tequila blanco']],
                ],
            ],
            [
                'slug' => 'vodka',
                'name' => 'Vodka',
                'type' => IngredientType::Spirit,
                'is_alcoholic' => true,
                'default_unit' => MeasurementUnit::Millilitre,
                'products' => [],
            ],
            [
                'slug' => 'brandy',
                'name' => 'Brandy',
                'type' => IngredientType::Spirit,
                'is_alcoholic' => true,
                'default_unit' => MeasurementUnit::Millilitre,
                'products' => [
                    ['slug' => 'cognac', 'name' => 'Cognac'],
                ],
            ],
            [
                'slug' => 'vermouth',
                'name' => 'Vermouth',
                'type' => IngredientType::Fortified,
                'is_alcoholic' => true,
                'default_unit' => MeasurementUnit::Millilitre,
                'products' => [
                    ['slug' => 'sweet-vermouth', 'name' => 'Sweet Vermouth', 'aliases' => ['red vermouth', 'rosso', 'italian vermouth']],
                    ['slug' => 'dry-vermouth', 'name' => 'Dry Vermouth', 'aliases' => ['white vermouth', 'french vermouth']],
                ],
            ],
            [
                'slug' => 'bitter-aperitivo',
                'name' => 'Bitter Aperitivo',
                'type' => IngredientType::Liqueur,
                'is_alcoholic' => true,
                'default_unit' => MeasurementUnit::Millilitre,
                'aliases' => ['campari-style bitter'],
                'products' => [
                    ['slug' => 'campari', 'name' => 'Campari'],
                    ['slug' => 'aperol', 'name' => 'Aperol'],
                ],
            ],
            [
                'slug' => 'liqueur',
                'name' => 'Liqueur',
                'type' => IngredientType::Liqueur,
                'is_alcoholic' => true,
                'default_unit' => MeasurementUnit::Millilitre,
                'products' => [
                    ['slug' => 'triple-sec', 'name' => 'Triple Sec', 'aliases' => ['orange liqueur', 'cointreau']],
                    ['slug' => 'maraschino', 'name' => 'Maraschino', 'aliases' => ['luxardo']],
                ],
            ],
            [
                'slug' => 'bitters',
                'name' => 'Bitters',
                'type' => IngredientType::Bitters,
                'is_alcoholic' => true,
                'default_unit' => MeasurementUnit::Dash,
                'products' => [
                    ['slug' => 'angostura', 'name' => 'Angostura Bitters', 'aliases' => ['aromatic bitters']],
                    ['slug' => 'orange-bitters', 'name' => 'Orange Bitters'],
                ],
            ],
            [
                'slug' => 'citrus',
                'name' => 'Citrus',
                'type' => IngredientType::Fresh,
                'is_alcoholic' => false,
                'default_unit' => MeasurementUnit::Count,
                'products' => [
                    ['slug' => 'lime', 'name' => 'Lime'],
                    ['slug' => 'lemon', 'name' => 'Lemon'],
                    ['slug' => 'orange', 'name' => 'Orange'],
                ],
            ],
            [
                'slug' => 'juice',
                'name' => 'Juice',
                'type' => IngredientType::Juice,
                'is_alcoholic' => false,
                'default_unit' => MeasurementUnit::Millilitre,
                'products' => [
                    ['slug' => 'lime-juice', 'name' => 'Lime Juice', 'aliases' => ['fresh lime juice']],
                    ['slug' => 'lemon-juice', 'name' => 'Lemon Juice', 'aliases' => ['fresh lemon juice']],
                ],
            ],
            [
                'slug' => 'syrup',
                'name' => 'Syrup',
                'type' => IngredientType::Syrup,
                'is_alcoholic' => false,
                'default_unit' => MeasurementUnit::Millilitre,
                'products' => [
                    ['slug' => 'simple-syrup', 'name' => 'Simple Syrup', 'aliases' => ['sugar syrup', 'gomme']],
                    ['slug' => 'orgeat', 'name' => 'Orgeat', 'aliases' => ['almond syrup']],
                ],
            ],
            [
                'slug' => 'mixer',
                'name' => 'Mixer',
                'type' => IngredientType::Mixer,
                'is_alcoholic' => false,
                'default_unit' => MeasurementUnit::Millilitre,
                'products' => [
                    ['slug' => 'soda-water', 'name' => 'Soda Water', 'aliases' => ['club soda', 'sparkling water']],
                    ['slug' => 'tonic-water', 'name' => 'Tonic Water', 'aliases' => ['tonic']],
                    ['slug' => 'ginger-beer', 'name' => 'Ginger Beer'],
                ],
            ],
            [
                'slug' => 'garnish',
                'name' => 'Garnish',
                'type' => IngredientType::Garnish,
                'is_alcoholic' => false,
                'default_unit' => MeasurementUnit::Count,
                'products' => [
                    ['slug' => 'orange-peel', 'name' => 'Orange Peel', 'aliases' => ['orange twist']],
                    ['slug' => 'lemon-peel', 'name' => 'Lemon Peel', 'aliases' => ['lemon twist']],
                    ['slug' => 'lime-wheel', 'name' => 'Lime Wheel', 'aliases' => ['lime wedge']],
                    ['slug' => 'mint', 'name' => 'Mint', 'aliases' => ['mint sprig']],
                    ['slug' => 'cocktail-cherry', 'name' => 'Cocktail Cherry', 'aliases' => ['maraschino cherry']],
                ],
            ],
        ];
    }

    /**
     * Seeded in both directions where the swap reads the same either way, so
     * the matcher finds it whichever side the recipe names.
     *
     * @return array<int, array{0: string, 1: string, 2: SubstituteQuality, 3: string|null}>
     */
    private function substitutions(): array
    {
        return [
            ['rye', 'bourbon', SubstituteQuality::Good, 'Sweeter and rounder than rye.'],
            ['bourbon', 'rye', SubstituteQuality::Good, 'Drier and spicier than bourbon.'],
            ['campari', 'aperol', SubstituteQuality::Acceptable, 'Lower proof and much sweeter.'],
            ['aperol', 'campari', SubstituteQuality::Acceptable, 'Drier and considerably more bitter.'],
            ['lime-juice', 'lemon-juice', SubstituteQuality::Acceptable, null],
            ['lemon-juice', 'lime-juice', SubstituteQuality::Acceptable, null],
            ['cognac', 'bourbon', SubstituteQuality::Poor, 'Changes the drink, but keeps it in the family.'],
            ['triple-sec', 'maraschino', SubstituteQuality::Poor, 'Far more aromatic; use less.'],
        ];
    }
}
