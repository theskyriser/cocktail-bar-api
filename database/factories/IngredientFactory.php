<?php

namespace Database\Factories;

use App\Enums\IngredientKind;
use App\Enums\IngredientType;
use App\Enums\MeasurementUnit;
use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Ingredient>
 */
class IngredientFactory extends Factory
{
    /**
     * The categories a recipe line can name, each with the type, alcohol and
     * unit that belong to it. Drawing the name and deriving the rest from this
     * map is what stops the factory producing a gin measured in dashes, or a
     * syrup filed under spirits.
     *
     * Names come from a unique pool, so one test may create at most this many
     * distinct categories before Faker overflows.
     *
     * @var array<string, array{IngredientType, bool, MeasurementUnit}>
     */
    private const array CATEGORIES = [
        'Gin' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'London Dry Gin' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Old Tom Gin' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Vodka' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'White Rum' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Dark Rum' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Aged Rum' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Cachaca' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Bourbon' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Rye Whiskey' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Scotch Whisky' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Irish Whiskey' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Blanco Tequila' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Reposado Tequila' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Mezcal' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Cognac' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Calvados' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Pisco' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Absinthe' => [IngredientType::Spirit, true, MeasurementUnit::Millilitre],
        'Bitter Aperitivo' => [IngredientType::Liqueur, true, MeasurementUnit::Millilitre],
        'Orange Liqueur' => [IngredientType::Liqueur, true, MeasurementUnit::Millilitre],
        'Maraschino Liqueur' => [IngredientType::Liqueur, true, MeasurementUnit::Millilitre],
        'Elderflower Liqueur' => [IngredientType::Liqueur, true, MeasurementUnit::Millilitre],
        'Coffee Liqueur' => [IngredientType::Liqueur, true, MeasurementUnit::Millilitre],
        'Creme de Cassis' => [IngredientType::Liqueur, true, MeasurementUnit::Millilitre],
        'Green Chartreuse' => [IngredientType::Liqueur, true, MeasurementUnit::Millilitre],
        'Sweet Vermouth' => [IngredientType::Fortified, true, MeasurementUnit::Millilitre],
        'Dry Vermouth' => [IngredientType::Fortified, true, MeasurementUnit::Millilitre],
        'Blanc Vermouth' => [IngredientType::Fortified, true, MeasurementUnit::Millilitre],
        'Fino Sherry' => [IngredientType::Fortified, true, MeasurementUnit::Millilitre],
        'Tawny Port' => [IngredientType::Fortified, true, MeasurementUnit::Millilitre],
        'Aromatic Bitters' => [IngredientType::Bitters, true, MeasurementUnit::Dash],
        'Orange Bitters' => [IngredientType::Bitters, true, MeasurementUnit::Dash],
        'Peach Bitters' => [IngredientType::Bitters, true, MeasurementUnit::Dash],
        'Celery Bitters' => [IngredientType::Bitters, true, MeasurementUnit::Dash],
        'Lime Juice' => [IngredientType::Juice, false, MeasurementUnit::Millilitre],
        'Lemon Juice' => [IngredientType::Juice, false, MeasurementUnit::Millilitre],
        'Grapefruit Juice' => [IngredientType::Juice, false, MeasurementUnit::Millilitre],
        'Pineapple Juice' => [IngredientType::Juice, false, MeasurementUnit::Millilitre],
        'Orange Juice' => [IngredientType::Juice, false, MeasurementUnit::Millilitre],
        'Limes' => [IngredientType::Fresh, false, MeasurementUnit::Count],
        'Lemons' => [IngredientType::Fresh, false, MeasurementUnit::Count],
        'Oranges' => [IngredientType::Fresh, false, MeasurementUnit::Count],
        'Eggs' => [IngredientType::Fresh, false, MeasurementUnit::Count],
        'Egg White' => [IngredientType::Fresh, false, MeasurementUnit::Millilitre],
        'Heavy Cream' => [IngredientType::Fresh, false, MeasurementUnit::Millilitre],
        'Simple Syrup' => [IngredientType::Syrup, false, MeasurementUnit::Millilitre],
        'Demerara Syrup' => [IngredientType::Syrup, false, MeasurementUnit::Millilitre],
        'Orgeat' => [IngredientType::Syrup, false, MeasurementUnit::Millilitre],
        'Grenadine' => [IngredientType::Syrup, false, MeasurementUnit::Millilitre],
        'Honey Syrup' => [IngredientType::Syrup, false, MeasurementUnit::Millilitre],
        'Soda Water' => [IngredientType::Mixer, false, MeasurementUnit::Millilitre],
        'Tonic Water' => [IngredientType::Mixer, false, MeasurementUnit::Millilitre],
        'Ginger Beer' => [IngredientType::Mixer, false, MeasurementUnit::Millilitre],
        'Cola' => [IngredientType::Mixer, false, MeasurementUnit::Millilitre],
        'Prosecco' => [IngredientType::Mixer, true, MeasurementUnit::Millilitre],
        'Mint Sprig' => [IngredientType::Garnish, false, MeasurementUnit::Count],
        'Orange Twist' => [IngredientType::Garnish, false, MeasurementUnit::Count],
        'Lemon Twist' => [IngredientType::Garnish, false, MeasurementUnit::Count],
        'Maraschino Cherry' => [IngredientType::Garnish, false, MeasurementUnit::Count],
        'Olive' => [IngredientType::Garnish, false, MeasurementUnit::Count],
    ];

    /**
     * Bottles that sit beneath a category. They carry no type, alcohol or unit
     * of their own — those come from the parent — so a name is all that is
     * needed here. Same unique-pool caveat as the categories.
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
    ];

    /**
     * Define the model's default state: a curated category, the row a recipe
     * line names. `path` is left out — the model's saving hook derives it.
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
     * A bottle beneath the given category, taking its type, alcohol and unit
     * from the parent rather than from the caller — the same inheritance
     * `IngredientSeeder` applies when it spreads a category over its products.
     * A product that disagrees with its category is a row the seeder can never
     * produce, so the factory does not offer a way to build one.
     */
    public function under(Ingredient $category): static
    {
        return $this->state(fn (array $attributes): array => [
            'parent_id' => $category->id,
            'kind' => IngredientKind::Product,
            'name' => (string) $this->faker->unique()->randomElement(self::PRODUCTS),
            'type' => $category->type,
            'is_alcoholic' => $category->is_alcoholic,
            'default_unit' => $category->default_unit,
        ]);
    }

    /**
     * A bottle beneath a category generated for it. The category is created as
     * the chain is built, not per row, so `count(3)->product()` gives three
     * bottles on one shelf.
     */
    public function product(?Ingredient $category = null): static
    {
        return $this->under($category ?? static::new()->createOne());
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
}
