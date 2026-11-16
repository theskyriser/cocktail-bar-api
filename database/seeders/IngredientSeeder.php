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
     *
     * Three tiers, and `kind` names each one rather than leaving it to depth:
     * a **category** is the broad group a filter chip names (Whiskey), a
     * **style** is what a recipe line asks for (Bourbon), and a **product** is
     * a brand someone puts on a shelf (Buffalo Trace). Depth is not uniform,
     * because brands are not uniform: Plymouth Gin is a brand with no style
     * above it, and a lime has no brand below it. Reading `kind` tells you
     * what a row is; counting the dots in `path` does not.
     */
    public function run(): void
    {
        /** @var array<string, Ingredient> $bySlug */
        $bySlug = [];

        foreach ($this->categories() as $category) {
            $parent = $this->upsert($category, IngredientKind::Category, null);
            $bySlug[$parent->slug] = $parent;

            foreach ($category['styles'] ?? [] as $style) {
                $styleRow = $this->upsert(
                    [...$this->inheritableFrom($parent), ...$style],
                    IngredientKind::Style,
                    $parent,
                );
                $bySlug[$styleRow->slug] = $styleRow;

                foreach ($style['products'] ?? [] as $brand) {
                    $brandRow = $this->upsert(
                        [...$this->inheritableFrom($styleRow), ...$brand],
                        IngredientKind::Product,
                        $styleRow,
                    );
                    $bySlug[$brandRow->slug] = $brandRow;
                }
            }

            foreach ($category['products'] ?? [] as $brand) {
                $brandRow = $this->upsert(
                    [...$this->inheritableFrom($parent), ...$brand],
                    IngredientKind::Product,
                    $parent,
                );
                $bySlug[$brandRow->slug] = $brandRow;
            }
        }

        $this->linkSubstitutes($bySlug);
    }

    /**
     * What a child takes from its parent unless it names its own. Read off the
     * saved row rather than the array, so a style that overrides its unit
     * passes that override down to its brands.
     *
     * @return array{type: IngredientType, is_alcoholic: bool, default_unit: MeasurementUnit}
     */
    private function inheritableFrom(Ingredient $parent): array
    {
        return [
            'type' => $parent->type,
            'is_alcoholic' => $parent->is_alcoholic,
            'default_unit' => $parent->default_unit,
        ];
    }

    /**
     * A recipe line against a category or a style is already satisfied by any
     * row beneath it, so only a stand-in that crosses the tree needs a row
     * here.
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
     * Styles and brands inherit their parent's type, strength and unit unless
     * they name their own. A category may carry brands directly, for the
     * spirits where no style sits in between.
     *
     * @return array<int, array{
     *     slug: string,
     *     name: string,
     *     type: IngredientType,
     *     is_alcoholic: bool,
     *     default_unit: MeasurementUnit,
     *     aliases?: array<int, string>,
     *     styles?: array<int, array{slug: string, name: string, aliases?: array<int, string>, type?: IngredientType, is_alcoholic?: bool, default_unit?: MeasurementUnit, products?: array<int, array{slug: string, name: string, aliases?: array<int, string>, type?: IngredientType, is_alcoholic?: bool, default_unit?: MeasurementUnit}>}>,
     *     products?: array<int, array{slug: string, name: string, aliases?: array<int, string>, type?: IngredientType, is_alcoholic?: bool, default_unit?: MeasurementUnit}>
     * }>
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
                'styles' => [
                    [
                        'slug' => 'london-dry-gin',
                        'name' => 'London Dry Gin',
                        'aliases' => ['london dry', 'dry gin'],
                        'products' => [
                            ['slug' => 'tanqueray', 'name' => 'Tanqueray London Dry', 'aliases' => ['tanqueray']],
                            ['slug' => 'beefeater', 'name' => 'Beefeater London Dry', 'aliases' => ['beefeater']],
                            ['slug' => 'sipsmith', 'name' => 'Sipsmith London Dry', 'aliases' => ['sipsmith']],
                        ],
                    ],
                    [
                        'slug' => 'old-tom-gin',
                        'name' => 'Old Tom Gin',
                        'aliases' => ['old tom'],
                        'products' => [
                            ['slug' => 'haymans-old-tom', 'name' => "Hayman's Old Tom"],
                        ],
                    ],
                ],
                'products' => [
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
                'styles' => [
                    [
                        'slug' => 'bourbon',
                        'name' => 'Bourbon',
                        'aliases' => ['bourbon whiskey'],
                        'products' => [
                            ['slug' => 'buffalo-trace', 'name' => 'Buffalo Trace'],
                            ['slug' => 'four-roses', 'name' => 'Four Roses Yellow Label', 'aliases' => ['four roses']],
                            ['slug' => 'woodford-reserve', 'name' => 'Woodford Reserve'],
                        ],
                    ],
                    [
                        'slug' => 'rye',
                        'name' => 'Rye',
                        'aliases' => ['rye whiskey'],
                        'products' => [
                            ['slug' => 'rittenhouse', 'name' => 'Rittenhouse Rye 100', 'aliases' => ['rittenhouse']],
                            ['slug' => 'sazerac-rye', 'name' => 'Sazerac Rye'],
                        ],
                    ],
                    [
                        'slug' => 'scotch',
                        'name' => 'Scotch',
                        'aliases' => ['scotch whisky'],
                        'products' => [
                            ['slug' => 'monkey-shoulder', 'name' => 'Monkey Shoulder'],
                            ['slug' => 'laphroaig-10', 'name' => 'Laphroaig 10'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'rum',
                'name' => 'Rum',
                'type' => IngredientType::Spirit,
                'is_alcoholic' => true,
                'default_unit' => MeasurementUnit::Millilitre,
                'styles' => [
                    [
                        'slug' => 'white-rum',
                        'name' => 'White Rum',
                        'aliases' => ['light rum', 'silver rum'],
                        'products' => [
                            ['slug' => 'havana-club-3', 'name' => 'Havana Club 3 Anos', 'aliases' => ['havana club']],
                            ['slug' => 'plantation-3-stars', 'name' => 'Plantation 3 Stars'],
                        ],
                    ],
                    [
                        'slug' => 'dark-rum',
                        'name' => 'Dark Rum',
                        'aliases' => ['aged rum'],
                        'products' => [
                            ['slug' => 'appleton-signature', 'name' => 'Appleton Estate Signature', 'aliases' => ['appleton']],
                            ['slug' => 'mount-gay-black-barrel', 'name' => 'Mount Gay Black Barrel'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'tequila',
                'name' => 'Tequila',
                'type' => IngredientType::Spirit,
                'is_alcoholic' => true,
                'default_unit' => MeasurementUnit::Millilitre,
                'styles' => [
                    [
                        'slug' => 'blanco-tequila',
                        'name' => 'Blanco Tequila',
                        'aliases' => ['silver tequila', 'tequila blanco'],
                        'products' => [
                            ['slug' => 'olmeca-altos-plata', 'name' => 'Olmeca Altos Plata', 'aliases' => ['altos']],
                            ['slug' => 'tapatio-blanco', 'name' => 'Tapatio Blanco'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'vodka',
                'name' => 'Vodka',
                'type' => IngredientType::Spirit,
                'is_alcoholic' => true,
                'default_unit' => MeasurementUnit::Millilitre,
                'products' => [
                    ['slug' => 'ketel-one', 'name' => 'Ketel One'],
                    ['slug' => 'absolut-blue', 'name' => 'Absolut Blue', 'aliases' => ['absolut']],
                ],
            ],
            [
                'slug' => 'brandy',
                'name' => 'Brandy',
                'type' => IngredientType::Spirit,
                'is_alcoholic' => true,
                'default_unit' => MeasurementUnit::Millilitre,
                'styles' => [
                    [
                        'slug' => 'cognac',
                        'name' => 'Cognac',
                        'products' => [
                            ['slug' => 'hennessy-vs', 'name' => 'Hennessy VS', 'aliases' => ['hennessy']],
                            ['slug' => 'pierre-ferrand-1840', 'name' => 'Pierre Ferrand 1840'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'vermouth',
                'name' => 'Vermouth',
                'type' => IngredientType::Fortified,
                'is_alcoholic' => true,
                'default_unit' => MeasurementUnit::Millilitre,
                'styles' => [
                    [
                        'slug' => 'sweet-vermouth',
                        'name' => 'Sweet Vermouth',
                        'aliases' => ['red vermouth', 'rosso', 'italian vermouth'],
                        'products' => [
                            ['slug' => 'carpano-antica', 'name' => 'Carpano Antica Formula', 'aliases' => ['carpano']],
                            ['slug' => 'martini-rosso', 'name' => 'Martini Rosso'],
                            ['slug' => 'cocchi-di-torino', 'name' => 'Cocchi Storico Vermouth di Torino', 'aliases' => ['cocchi']],
                        ],
                    ],
                    [
                        'slug' => 'dry-vermouth',
                        'name' => 'Dry Vermouth',
                        'aliases' => ['white vermouth', 'french vermouth'],
                        'products' => [
                            ['slug' => 'dolin-dry', 'name' => 'Dolin Dry', 'aliases' => ['dolin']],
                            ['slug' => 'noilly-prat', 'name' => 'Noilly Prat Original Dry', 'aliases' => ['noilly prat']],
                        ],
                    ],
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
                    ['slug' => 'select-aperitivo', 'name' => 'Select Aperitivo', 'aliases' => ['select']],
                ],
            ],
            [
                'slug' => 'liqueur',
                'name' => 'Liqueur',
                'type' => IngredientType::Liqueur,
                'is_alcoholic' => true,
                'default_unit' => MeasurementUnit::Millilitre,
                'styles' => [
                    [
                        'slug' => 'triple-sec',
                        'name' => 'Triple Sec',
                        'aliases' => ['orange liqueur'],
                        'products' => [
                            ['slug' => 'cointreau', 'name' => 'Cointreau'],
                            ['slug' => 'ferrand-dry-curacao', 'name' => 'Pierre Ferrand Dry Curacao'],
                        ],
                    ],
                    [
                        'slug' => 'maraschino',
                        'name' => 'Maraschino',
                        'products' => [
                            ['slug' => 'luxardo-maraschino', 'name' => 'Luxardo Maraschino', 'aliases' => ['luxardo']],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'bitters',
                'name' => 'Bitters',
                'type' => IngredientType::Bitters,
                'is_alcoholic' => true,
                'default_unit' => MeasurementUnit::Dash,
                'styles' => [
                    [
                        'slug' => 'aromatic-bitters',
                        'name' => 'Aromatic Bitters',
                        'products' => [
                            ['slug' => 'angostura', 'name' => 'Angostura Bitters'],
                            ['slug' => 'peychauds', 'name' => "Peychaud's Bitters", 'aliases' => ['peychauds']],
                        ],
                    ],
                    [
                        'slug' => 'orange-bitters',
                        'name' => 'Orange Bitters',
                        'products' => [
                            ['slug' => 'regans-orange-bitters', 'name' => "Regans' Orange Bitters No. 6", 'aliases' => ['regans']],
                            ['slug' => 'fee-brothers-orange-bitters', 'name' => 'Fee Brothers Orange Bitters'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'citrus',
                'name' => 'Citrus',
                'type' => IngredientType::Fresh,
                'is_alcoholic' => false,
                'default_unit' => MeasurementUnit::Count,
                'styles' => [
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
                'styles' => [
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
                'styles' => [
                    ['slug' => 'simple-syrup', 'name' => 'Simple Syrup', 'aliases' => ['sugar syrup', 'gomme']],
                    [
                        'slug' => 'orgeat',
                        'name' => 'Orgeat',
                        'aliases' => ['almond syrup'],
                        'products' => [
                            ['slug' => 'giffard-orgeat', 'name' => 'Giffard Orgeat'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'mixer',
                'name' => 'Mixer',
                'type' => IngredientType::Mixer,
                'is_alcoholic' => false,
                'default_unit' => MeasurementUnit::Millilitre,
                'styles' => [
                    [
                        'slug' => 'soda-water',
                        'name' => 'Soda Water',
                        'aliases' => ['club soda', 'sparkling water'],
                        'products' => [
                            ['slug' => 'fever-tree-soda', 'name' => 'Fever-Tree Soda Water'],
                        ],
                    ],
                    [
                        'slug' => 'tonic-water',
                        'name' => 'Tonic Water',
                        'aliases' => ['tonic'],
                        'products' => [
                            ['slug' => 'fever-tree-tonic', 'name' => 'Fever-Tree Indian Tonic'],
                        ],
                    ],
                    [
                        'slug' => 'ginger-beer',
                        'name' => 'Ginger Beer',
                        'products' => [
                            ['slug' => 'bundaberg-ginger-beer', 'name' => 'Bundaberg Ginger Beer', 'aliases' => ['bundaberg']],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'garnish',
                'name' => 'Garnish',
                'type' => IngredientType::Garnish,
                'is_alcoholic' => false,
                'default_unit' => MeasurementUnit::Count,
                'styles' => [
                    ['slug' => 'orange-peel', 'name' => 'Orange Peel', 'aliases' => ['orange twist']],
                    ['slug' => 'lemon-peel', 'name' => 'Lemon Peel', 'aliases' => ['lemon twist']],
                    ['slug' => 'lime-wheel', 'name' => 'Lime Wheel', 'aliases' => ['lime wedge']],
                    ['slug' => 'mint', 'name' => 'Mint', 'aliases' => ['mint sprig']],
                    [
                        'slug' => 'cocktail-cherry',
                        'name' => 'Cocktail Cherry',
                        'aliases' => ['maraschino cherry'],
                        'products' => [
                            ['slug' => 'luxardo-cherries', 'name' => 'Luxardo Maraschino Cherries'],
                        ],
                    ],
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
