<?php

namespace Database\Factories;

use App\Enums\CategoryType;
use App\Enums\IngredientType;
use App\Enums\IngredientUnit;
use App\Models\Ingredient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Ingredient>
 */
class IngredientFactory extends Factory
{
    /**
     * Recipe-facing categories, each mapped to the grouping it belongs to, so a
     * generated category is never a gin filed under syrups. Names are drawn from
     * a unique pool, so a single test may create at most this many distinct
     * categories before Faker overflows.
     *
     * @var array<string, CategoryType>
     */
    private const array CATEGORIES = [
        'Gin' => CategoryType::Spirit,
        'London Dry Gin' => CategoryType::Spirit,
        'Old Tom Gin' => CategoryType::Spirit,
        'Vodka' => CategoryType::Spirit,
        'White Rum' => CategoryType::Spirit,
        'Dark Rum' => CategoryType::Spirit,
        'Aged Rum' => CategoryType::Spirit,
        'Cachaca' => CategoryType::Spirit,
        'Bourbon' => CategoryType::Spirit,
        'Rye Whiskey' => CategoryType::Spirit,
        'Scotch Whisky' => CategoryType::Spirit,
        'Irish Whiskey' => CategoryType::Spirit,
        'Blanco Tequila' => CategoryType::Spirit,
        'Reposado Tequila' => CategoryType::Spirit,
        'Mezcal' => CategoryType::Spirit,
        'Cognac' => CategoryType::Spirit,
        'Calvados' => CategoryType::Spirit,
        'Pisco' => CategoryType::Spirit,
        'Absinthe' => CategoryType::Spirit,
        'Sweet Vermouth' => CategoryType::Vermouth,
        'Dry Vermouth' => CategoryType::Vermouth,
        'Blanc Vermouth' => CategoryType::Vermouth,
        'Bitter Aperitivo' => CategoryType::Liqueur,
        'Orange Liqueur' => CategoryType::Liqueur,
        'Maraschino Liqueur' => CategoryType::Liqueur,
        'Elderflower Liqueur' => CategoryType::Liqueur,
        'Coffee Liqueur' => CategoryType::Liqueur,
        'Creme de Cassis' => CategoryType::Liqueur,
        'Green Chartreuse' => CategoryType::Liqueur,
        'Aromatic Bitters' => CategoryType::Bitters,
        'Orange Bitters' => CategoryType::Bitters,
        'Peach Bitters' => CategoryType::Bitters,
        'Lime Juice' => CategoryType::Fresh,
        'Lemon Juice' => CategoryType::Fresh,
        'Grapefruit Juice' => CategoryType::Fresh,
        'Pineapple Juice' => CategoryType::Fresh,
        'Egg White' => CategoryType::Fresh,
        'Heavy Cream' => CategoryType::Fresh,
        'Mint' => CategoryType::Fresh,
        'Lime' => CategoryType::Fresh,
        'Lemon' => CategoryType::Fresh,
        'Orange' => CategoryType::Fresh,
        'Simple Syrup' => CategoryType::Syrup,
        'Demerara Syrup' => CategoryType::Syrup,
        'Orgeat' => CategoryType::Syrup,
        'Grenadine' => CategoryType::Syrup,
        'Honey Syrup' => CategoryType::Syrup,
        'Soda Water' => CategoryType::Mixer,
        'Tonic Water' => CategoryType::Mixer,
        'Ginger Beer' => CategoryType::Mixer,
        'Cola' => CategoryType::Mixer,
        'Prosecco' => CategoryType::Mixer,
    ];

    /**
     * Stockable bottles. Same unique-pool caveat as the categories.
     *
     * @var list<string>
     */
    private const array PRODUCT_NAMES = [
        'Tanqueray London Dry', 'Beefeater London Dry', "Gordon's Special Dry",
        'Sipsmith London Dry', "Hayman's Old Tom", 'Plymouth Original',
        'Ketel One', 'Belvedere', 'Absolut Blue', 'Havana Club 3 Anos',
        'Plantation 3 Stars', 'Appleton Estate Signature', 'Mount Gay Black Barrel',
        'Diplomatico Reserva Exclusiva', 'Buffalo Trace', 'Four Roses Yellow Label',
        'Woodford Reserve', 'Rittenhouse Rye 100', 'Sazerac Rye',
        "Michter's US*1 Rye", 'Monkey Shoulder', 'Laphroaig 10',
        'Redbreast 12', 'Olmeca Altos Plata', 'Tapatio Blanco',
        'Fortaleza Reposado', 'Del Maguey Vida', 'Hennessy VS', 'Pierre Ferrand 1840',
        'Cocchi Storico Vermouth di Torino', 'Carpano Antica Formula',
        'Martini Rosso', 'Dolin Dry', 'Noilly Prat Original Dry',
        'Lillet Blanc', 'Campari', 'Aperol', 'Select Aperitivo',
        'Cointreau', 'Grand Marnier', 'Pierre Ferrand Dry Curacao',
        'Luxardo Maraschino', 'St-Germain', 'Mr Black Cold Brew',
        'Giffard Creme de Cassis', 'Green Chartreuse VEP', 'Pernod Absinthe',
        'Angostura Aromatic Bitters', "Peychaud's Bitters", 'Regans Orange Bitters',
        'Fever-Tree Indian Tonic', 'Fever-Tree Soda Water', 'Bundaberg Ginger Beer',
    ];

    /**
     * Define the model's default state: a top-level, recipe-facing category.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'type' => IngredientType::Category,
            'name' => fn (): string => (string) fake()->unique()->randomElement(array_keys(self::CATEGORIES)),
            'category_type' => fn (array $attributes): CategoryType => self::CATEGORIES[(string) $attributes['name']],
            'slug' => fn (array $attributes): string => Str::slug((string) $attributes['name']),
            'unit' => IngredientUnit::Millilitres,
            'substitute_group' => null,
            'bottle_size_ml' => null,
        ];
    }

    /**
     * A recipe-facing category. This is the default state, stated explicitly.
     */
    public function category(): static
    {
        return $this->state(fn (): array => [
            'parent_id' => null,
            'type' => IngredientType::Category,
            'name' => fn (): string => (string) fake()->unique()->randomElement(array_keys(self::CATEGORIES)),
            'category_type' => fn (array $attributes): CategoryType => self::CATEGORIES[(string) $attributes['name']],
            'bottle_size_ml' => null,
        ]);
    }

    /**
     * A category in a specific grouping, with a name drawn from that grouping.
     */
    public function categoryType(CategoryType $categoryType): static
    {
        $names = array_keys(array_filter(
            self::CATEGORIES,
            fn (CategoryType $type): bool => $type === $categoryType,
        ));

        return $this->category()->state(fn (): array => [
            'name' => fn (): string => (string) fake()->unique()->randomElement($names),
            'category_type' => $categoryType,
        ]);
    }

    /**
     * A stockable product, without saying which category it belongs to. Use this
     * when the parent is supplied by a relationship — `withProducts()` or
     * `->has(…, 'products')` — and `product()` otherwise. The grouping lives on
     * the category, so a product never carries one.
     */
    public function asProduct(): static
    {
        return $this->state(fn (): array => [
            'type' => IngredientType::Product,
            'category_type' => null,
            'name' => fn (): string => (string) fake()->unique()->randomElement(self::PRODUCT_NAMES),
            'bottle_size_ml' => fake()->randomElement([375, 500, 700, 750, 1000]),
        ]);
    }

    /**
     * A stockable product under the given category, or under a generated one.
     */
    public function product(?Ingredient $category = null): static
    {
        return $this->asProduct()->state(fn (): array => [
            'parent_id' => $category?->getKey() ?? static::new()->category(),
        ]);
    }

    /**
     * Hang this row off an existing category.
     */
    public function under(Ingredient $category): static
    {
        return $this->state(fn (): array => [
            'parent_id' => $category->getKey(),
        ]);
    }

    /**
     * A category with products already under it.
     */
    public function withProducts(int $count = 3): static
    {
        return $this->category()->has(
            static::new()->count($count)->asProduct(),
            'products'
        );
    }

    public function unit(IngredientUnit $unit): static
    {
        return $this->state(fn (): array => [
            'unit' => $unit,
        ]);
    }

    /**
     * Measured by the piece — limes, eggs, mint sprigs. Never bottled.
     */
    public function countable(): static
    {
        return $this->state(fn (): array => [
            'unit' => IngredientUnit::Count,
            'bottle_size_ml' => null,
        ]);
    }

    /**
     * Measured in dashes — bitters, absinthe rinses.
     */
    public function dashed(): static
    {
        return $this->state(fn (): array => [
            'unit' => IngredientUnit::Dash,
        ]);
    }

    public function substituteGroup(string $group): static
    {
        return $this->state(fn (): array => [
            'substitute_group' => $group,
        ]);
    }
}
