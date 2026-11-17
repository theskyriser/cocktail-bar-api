<?php

namespace Database\Factories;

use App\Enums\IngredientKind;
use App\Enums\IngredientType;
use App\Enums\MeasurementUnit;
use App\Models\Ingredient;
use App\Models\IngredientAlias;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Ingredient>
 */
class IngredientFactory extends Factory
{
    /**
     * The broad groups a filter chip names, each with the type, alcohol and
     * unit that belong to it. Every `IngredientType` case appears at least
     * once, which is what lets `style()` always find a name to match a
     * parent's type.
     *
     * Names come from a unique pool, so one test may create at most this many
     * distinct categories before Faker overflows.
     *
     * @var array<string, array{IngredientType, bool, MeasurementUnit}>
     */
    private const array CATEGORIES = [
        'Gin' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Whiskey' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Rum' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Tequila' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Vodka' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Brandy' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Mezcal' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Cachaca' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Pisco' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Absinthe' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Vermouth' => [IngredientType::Fortified, true, MeasurementUnit::Millilitre],
        'Sherry' => [IngredientType::Fortified, true, MeasurementUnit::Millilitre],
        'Port' => [IngredientType::Fortified, true, MeasurementUnit::Millilitre],
        'Liqueur' => [IngredientType::Liqueur, true, MeasurementUnit::Millilitre],
        'Bitter Aperitivo' => [IngredientType::Liqueur, true, MeasurementUnit::Millilitre],
        'Amaro' => [IngredientType::Liqueur, true, MeasurementUnit::Millilitre],
        'Bitters' => [IngredientType::Bitters, true, MeasurementUnit::Dash],
        'Citrus' => [IngredientType::Fresh, false, MeasurementUnit::Count],
        'Dairy' => [IngredientType::Fresh, false, MeasurementUnit::Millilitre],
        'Juice' => [IngredientType::Juice, false, MeasurementUnit::Millilitre],
        'Syrup' => [IngredientType::Syrup, false, MeasurementUnit::Millilitre],
        'Mixer' => [IngredientType::Mixer, false, MeasurementUnit::Millilitre],
        'Garnish' => [IngredientType::Garnish, false, MeasurementUnit::Count],
    ];

    /**
     * What a recipe line asks for. Same unique-pool caveat, and the same
     * type-coverage guarantee as the categories.
     *
     * @var array<string, array{IngredientType, bool, MeasurementUnit}>
     */
    private const array STYLES = [
        'London Dry Gin' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Old Tom Gin' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Bourbon' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Rye' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Scotch' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Irish Whiskey' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'White Rum' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Dark Rum' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Aged Rum' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Blanco Tequila' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Reposado Tequila' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Cognac' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Armagnac' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Calvados' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Sweet Vermouth' => [IngredientType::Fortified, true, MeasurementUnit::Millilitre],
        'Dry Vermouth' => [IngredientType::Fortified, true, MeasurementUnit::Millilitre],
        'Blanc Vermouth' => [IngredientType::Fortified, true, MeasurementUnit::Millilitre],
        'Fino Sherry' => [IngredientType::Fortified, true, MeasurementUnit::Millilitre],
        'Tawny Port' => [IngredientType::Fortified, true, MeasurementUnit::Millilitre],
        'Triple Sec' => [IngredientType::Liqueur, true, MeasurementUnit::Millilitre],
        'Maraschino' => [IngredientType::Liqueur, true, MeasurementUnit::Millilitre],
        'Creme de Cassis' => [IngredientType::Liqueur, true, MeasurementUnit::Millilitre],
        'Elderflower Liqueur' => [IngredientType::Liqueur, true, MeasurementUnit::Millilitre],
        'Coffee Liqueur' => [IngredientType::Liqueur, true, MeasurementUnit::Millilitre],
        'Aromatic Bitters' => [IngredientType::Bitters, true, MeasurementUnit::Dash],
        'Orange Bitters' => [IngredientType::Bitters, true, MeasurementUnit::Dash],
        'Peach Bitters' => [IngredientType::Bitters, true, MeasurementUnit::Dash],
        'Celery Bitters' => [IngredientType::Bitters, true, MeasurementUnit::Dash],
        'Lime' => [IngredientType::Fresh, false, MeasurementUnit::Count],
        'Lemon' => [IngredientType::Fresh, false, MeasurementUnit::Count],
        'Orange' => [IngredientType::Fresh, false, MeasurementUnit::Count],
        'Grapefruit' => [IngredientType::Fresh, false, MeasurementUnit::Count],
        'Egg White' => [IngredientType::Fresh, false, MeasurementUnit::Millilitre],
        'Heavy Cream' => [IngredientType::Fresh, false, MeasurementUnit::Millilitre],
        'Lime Juice' => [IngredientType::Juice, false, MeasurementUnit::Millilitre],
        'Lemon Juice' => [IngredientType::Juice, false, MeasurementUnit::Millilitre],
        'Grapefruit Juice' => [IngredientType::Juice, false, MeasurementUnit::Millilitre],
        'Pineapple Juice' => [IngredientType::Juice, false, MeasurementUnit::Millilitre],
        'Simple Syrup' => [IngredientType::Syrup, false, MeasurementUnit::Millilitre],
        'Demerara Syrup' => [IngredientType::Syrup, false, MeasurementUnit::Millilitre],
        'Orgeat' => [IngredientType::Syrup, false, MeasurementUnit::Millilitre],
        'Grenadine' => [IngredientType::Syrup, false, MeasurementUnit::Millilitre],
        'Honey Syrup' => [IngredientType::Syrup, false, MeasurementUnit::Millilitre],
        'Soda Water' => [IngredientType::Mixer, false, MeasurementUnit::Millilitre],
        'Tonic Water' => [IngredientType::Mixer, false, MeasurementUnit::Millilitre],
        'Ginger Beer' => [IngredientType::Mixer, false, MeasurementUnit::Millilitre],
        'Cola' => [IngredientType::Mixer, false, MeasurementUnit::Millilitre],
        'Mint' => [IngredientType::Garnish, false, MeasurementUnit::Count],
        'Orange Peel' => [IngredientType::Garnish, false, MeasurementUnit::Count],
        'Lemon Peel' => [IngredientType::Garnish, false, MeasurementUnit::Count],
        'Lime Wheel' => [IngredientType::Garnish, false, MeasurementUnit::Count],
        'Cocktail Cherry' => [IngredientType::Garnish, false, MeasurementUnit::Count],
        'Olive' => [IngredientType::Garnish, false, MeasurementUnit::Count],
    ];

    /**
     * Brands — what someone actually puts on a shelf. They carry no type,
     * alcohol or unit of their own; those come from the parent, so a name is
     * all that is needed here. Same unique-pool caveat.
     *
     * @var list<string>
     */
    private const array PRODUCTS = [
        'Tanqueray London Dry', 'Beefeater London Dry', "Gordon's Special Dry",
        'Sipsmith London Dry', "Hayman's Old Tom", 'Plymouth Original',
        'Ketel One', 'Belvedere', 'Absolut Blue', 'Havana Club 3 Anos',
        'Plantation 3 Stars', 'Appleton Estate Signature', 'Mount Gay Black Barrel',
        'Diplomatico Reserva Exclusiva', 'Buffalo Trace', 'Four Roses Yellow Label',
        'Woodford Reserve', 'Rittenhouse Rye 100', 'Sazerac Rye',
        "Michter's US 1 Rye", 'Monkey Shoulder', 'Laphroaig 10', 'Redbreast 12',
        'Olmeca Altos Plata', 'Tapatio Blanco', 'Fortaleza Reposado',
        'Del Maguey Vida', 'Hennessy VS', 'Pierre Ferrand 1840',
        'Cocchi Storico Vermouth di Torino', 'Carpano Antica Formula',
        'Martini Rosso', 'Dolin Dry', 'Noilly Prat Original Dry', 'Lillet Blanc',
        'Campari', 'Aperol', 'Select Aperitivo', 'Cointreau', 'Grand Marnier',
        'Pierre Ferrand Dry Curacao', 'Luxardo Maraschino', 'St-Germain',
        'Mr Black Cold Brew', 'Giffard Creme de Cassis', 'Green Chartreuse VEP',
        'Pernod Absinthe', 'Angostura Aromatic Bitters', "Peychaud's Bitters",
        'Regans Orange Bitters No 6', 'Fever-Tree Indian Tonic',
        'Fever-Tree Soda Water', 'Bundaberg Ginger Beer', 'Giffard Orgeat',
        'Luxardo Maraschino Cherries', 'Fee Brothers Orange Bitters',
    ];

    /**
     * Define the model's default state: a curated category, the broad group at
     * the root of the tree. `path` is left out — the model's saving hook
     * derives it.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = (string) $this->faker->unique()->randomElement(array_keys(self::CATEGORIES));
        [$type, $isAlcoholic, $unit] = self::CATEGORIES[$name];

        return [
            'parent_id' => null,
            'kind' => IngredientKind::Category,
            'name' => $name,
            'slug' => fn (array $attributes): string => Str::slug((string) $attributes['name']),
            'type' => $type,
            'is_alcoholic' => $isAlcoholic,
            'default_unit' => $unit,
            'created_by_user_id' => null,
        ];
    }

    /**
     * A category. This is the default state, stated explicitly.
     */
    public function category(): static
    {
        return $this->state(fn (array $attributes): array => [
            'parent_id' => null,
            'kind' => IngredientKind::Category,
        ]);
    }

    /**
     * A style — what a recipe line asks for — beneath the given category, or
     * beneath one generated for it. The name is drawn from the styles matching
     * the parent's type, so a Bourbon never lands under Garnish. Type, alcohol
     * and unit come from the parent, not from the caller.
     *
     * The category is resolved as the chain is built, not per row, so
     * `count(3)->style()` gives three styles under one category.
     */
    public function style(?Ingredient $category = null): static
    {
        $parent = $category ?? static::new()->createOne();
        $names = $this->styleNamesFor($parent->type);

        return $this->state(fn (array $attributes): array => [
            'parent_id' => $parent->id,
            'kind' => IngredientKind::Style,
            'name' => (string) $this->faker->unique()->randomElement($names),
            'type' => $parent->type,
            'is_alcoholic' => $parent->is_alcoholic,
            'default_unit' => $parent->default_unit,
        ]);
    }

    public function withAlias(?string $alias) : static
    {
        return $this->has(IngredientAlias::factory()->state(array_filter(['alias' => $alias])), 'aliases');
    }

    /**
     * A brand beneath the given row, taking its type, alcohol and unit from
     * that parent rather than from the caller — the same inheritance
     * `IngredientSeeder` applies. A brand that disagrees with what it is a
     * brand of is a row the seeder can never produce, so the factory offers no
     * way to build one.
     *
     * The parent may be a style (Buffalo Trace under Bourbon) or a category
     * directly (Plymouth Gin under Gin), because brands are not uniform in
     * depth.
     */
    public function under(Ingredient $parent): static
    {
        return $this->state(fn (array $attributes): array => [
            'parent_id' => $parent->id,
            'kind' => IngredientKind::Product,
            'name' => (string) $this->faker->unique()->randomElement(self::PRODUCTS),
            'type' => $parent->type,
            'is_alcoholic' => $parent->is_alcoholic,
            'default_unit' => $parent->default_unit,
        ]);
    }

    /**
     * A brand beneath the given row, or beneath a whole category-and-style
     * chain generated for it. Resolved as the chain is built, so
     * `count(3)->product()` gives three brands of one style.
     */
    public function product(?Ingredient $parent = null): static
    {
        return $this->under($parent ?? static::new()->style()->createOne());
    }

    /**
     * A category of the given type, named from that type's own entries, so the
     * name and the type always agree.
     */
    public function ofType(IngredientType $type): static
    {
        $names = array_keys(array_filter(
            self::CATEGORIES,
            fn (array $attributes): bool => $attributes[0] === $type,
        ));

        return $this->category()->state(function (array $attributes) use ($names): array {
            $name = (string) $this->faker->unique()->randomElement($names);
            [$pickedType, $isAlcoholic, $unit] = self::CATEGORIES[$name];

            return [
                'name' => $name,
                'type' => $pickedType,
                'is_alcoholic' => $isAlcoholic,
                'default_unit' => $unit,
            ];
        });
    }
    public function alcoholic(bool $isAlcoholic = true): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_alcoholic' => $isAlcoholic,
        ]);
    }

    public function measuredIn(MeasurementUnit $unit): static
    {
        return $this->state(fn (array $attributes): array => [
            'default_unit' => $unit,
        ]);
    }

    /**
     * An ingredient one user added for themselves, which the curated indexes
     * and the `curated` scope both exclude.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'created_by_user_id' => $user->id,
        ]);
    }

    /**
     * Every `IngredientType` case appears in `STYLES`, so this is never empty
     * for any type a parent can hold.
     *
     * @return list<string>
     */
    private function styleNamesFor(IngredientType $type): array
    {
        return array_keys(array_filter(
            self::STYLES,
            fn (array $attributes): bool => $attributes[0] === $type,
        ));
    }
}
