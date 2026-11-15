<?php

namespace Tests\Feature;

use App\Enums\IngredientKind;
use App\Enums\IngredientType;
use App\Enums\MeasurementUnit;
use App\Models\Bar;
use App\Models\BarStock;
use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarStockListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * `Model::preventLazyLoading()` sets a static that Laravel's testing
     * TestCase does not reset, so without this every test running after
     * the N+1 one below inherits strict mode.
     */
    protected function tearDown(): void
    {
        Model::preventLazyLoading(false);

        parent::tearDown();
    }

    public function test_it_requires_authentication(): void
    {
        $this->getJson(route('bars.index'))->assertUnauthorized();
    }

    /**
     * The contract test: one assertion pinning the whole payload, so a change
     * to a key, its casing, the nesting or the ordering fails here rather than
     * in the client's zod schema.
     */
    public function test_it_returns_the_bar_with_its_stock_grouped_by_type(): void
    {
        $user = User::factory()->create();
        $this->homeBar($user);

        $this->actingAs($user)
            ->getJson(route('bars.index'))
            ->assertOk()
            ->assertExactJson([
                'name' => 'Home Bar',
                'categories' => [
                    [
                        'id' => 8,
                        'type' => 'bitters',
                        'name' => 'Bitters',
                        'items' => [
                            [
                                'id' => 4,
                                'name' => 'Angostura Bitters',
                                'amount' => 60,
                                'bottle_size' => 100,
                                'default_unit' => MeasurementUnit::Dash,
                                'percentage_filled' => 0.6,
                            ],
                        ],
                    ],
                    [
                        'id' => 9,
                        'type' => 'fresh',
                        'name' => 'Fresh',
                        'items' => [
                            [
                                'id' => 5,
                                'name' => 'Lime',
                                'amount' => 3,
                                'bottle_size' => null,
                                'default_unit' => MeasurementUnit::Count,
                                'percentage_filled' => null,
                            ],
                        ],
                    ],
                    [
                        'id' => 5,
                        'type' => 'spirit',
                        'name' => 'Spirits',
                        'items' => [
                            [
                                'id' => 2,
                                'name' => 'London Dry Gin',
                                'amount' => 520,
                                'bottle_size' => 700,
                                'default_unit' => MeasurementUnit::Millilitre,
                                'percentage_filled' => 0.74,
                            ],
                            [
                                'id' => 3,
                                'name' => 'Plain Vodka',
                                'amount' => 300,
                                'bottle_size' => 700,
                                'default_unit' => MeasurementUnit::Millilitre,
                                'percentage_filled' => 0.43,
                            ],
                            [
                                'id' => 1,
                                'name' => 'Plymouth Gin',
                                'amount' => 400,
                                'bottle_size' => 700,
                                'default_unit' => MeasurementUnit::Millilitre,
                                'percentage_filled' => 0.57,
                            ],
                        ],
                    ],
                ],
            ]);
    }

    /**
     * Gin and Vodka are different categories of the same type, so every bottle
     * beneath either of them belongs to one `spirit` group. Naming that group
     * after a category — any category — files Vodka under "Gin".
     */
    public function test_it_merges_every_category_of_one_type_into_a_single_group(): void
    {
        $user = User::factory()->create();
        $this->homeBar($user);

        $categories = $this->actingAs($user)
            ->getJson(route('bars.index'))
            ->assertOk()
            ->json('categories');

        $spirits = collect($categories)->where('type', 'spirit');

        $this->assertCount(1, $spirits, 'Gin and Vodka were not merged into one spirit group.');
        $this->assertSame(
            ['London Dry Gin', 'Plain Vodka', 'Plymouth Gin'],
            array_column($spirits->first()['items'], 'name'),
        );
    }

    /**
     * The group is identified by the type, so its heading has to come from the
     * type too. Reading it off `$group->first()->ingredient->parent` picks an
     * arbitrary member's category, and also breaks outright on a bottle whose
     * ingredient has no parent.
     */
    public function test_it_labels_each_group_from_the_type_not_a_member_category(): void
    {
        $user = User::factory()->create();
        $this->homeBar($user);

        $spirits = $this->group($this->actingAs($user)->getJson(route('bars.index'))->json('categories'), 'spirit');

        $this->assertSame('Spirits', $spirits['name']);
    }

    /**
     * `Collection::groupBy()` returns a keyed collection, which encodes as a
     * JSON object. The client's schema expects an array, so the keys have to be
     * discarded before the payload leaves.
     */
    public function test_it_returns_categories_as_a_json_array_not_an_object(): void
    {
        $user = User::factory()->create();
        $this->homeBar($user);

        $payload = $this->actingAs($user)
            ->getJson(route('bars.index'))
            ->assertOk()
            ->json();

        $this->assertIsList($payload['categories']);

        foreach ($payload['categories'] as $category) {
            $this->assertIsList($category['items']);
        }
    }

    /**
     * Alphabetically by the type itself. Every case `IngredientType` declares
     * shares a first letter with its heading, so this fixture cannot tell
     * sorting by type from sorting by label — the assertion names the rule the
     * code implements rather than leaving that to be inferred.
     */
    public function test_it_orders_groups_alphabetically_by_type(): void
    {
        $user = User::factory()->create();
        $this->homeBar($user);

        $types = array_column(
            $this->actingAs($user)->getJson(route('bars.index'))->json('categories'),
            'type',
        );

        $this->assertSame(['bitters', 'fresh', 'spirit'], $types);
    }

    public function test_it_orders_items_within_a_group_by_name(): void
    {
        $user = User::factory()->create();
        $this->homeBar($user);

        $categories = $this->actingAs($user)
            ->getJson(route('bars.index'))
            ->assertOk()
            ->json('categories');

        foreach ($categories as $category) {
            $items = array_column($category['items'], 'name');
            $sorted = $items;
            sort($sorted);

            $this->assertSame($sorted, $items, "Items under {$category['name']} are not ordered by name.");
        }
    }

    /**
     * `bar_stock` stores a bare number and `BarStock`'s docblock sends the
     * reader to `ingredient.default_unit` for its meaning, so the payload has
     * to carry the unit from the ingredient or `amount` means nothing.
     */
    public function test_it_reads_the_measurement_unit_from_each_ingredient(): void
    {
        $user = User::factory()->create();
        $this->homeBar($user);

        $units = collect($this->actingAs($user)->getJson(route('bars.index'))->json('categories'))
            ->flatMap(fn (array $category): array => $category['items'])
            ->mapWithKeys(fn (array $item): array => [$item['name'] => $item['default_unit']]);

        $this->assertSame('ml', $units->get('London Dry Gin'));
        $this->assertSame('dash', $units->get('Angostura Bitters'));
        $this->assertSame('count', $units->get('Lime'));
    }

    /**
     * A bottle stocked straight against a category has no parent, which the
     * schema allows and nothing forbids. Grouping on the ingredient's own type
     * never dereferences a parent, so this case costs nothing — reaching
     * through `ingredient.parent` turns it into a TypeError.
     */
    public function test_it_includes_a_bottle_stocked_against_a_category_itself(): void
    {
        $user = User::factory()->create();
        $bar = $this->homeBar($user);

        $rum = $this->category('rum', 'Rum', IngredientType::Spirit, MeasurementUnit::Millilitre);
        $this->stocked($bar, $rum, amount: 700, bottleSize: 700);

        $categories = $this->actingAs($user)
            ->getJson(route('bars.index'))
            ->assertOk()
            ->json('categories');

        $this->assertContains('Rum', array_column($this->group($categories, 'spirit')['items'], 'name'));
    }

    /**
     * Fails with the type that was missing rather than letting a null index
     * through as a PHP error two lines later.
     *
     * @param  array<int, array<string, mixed>>  $categories
     * @return array<string, mixed>
     */
    protected function group(array $categories, string $type): array
    {
        $group = collect($categories)->firstWhere('type', $type);

        $this->assertIsArray($group, "The payload has no '{$type}' group.");

        return $group;
    }

    /**
     * The DTO reaches through the stock's ingredient, and the only thing
     * keeping that off the database once per bottle is the eager load in
     * `ListBarsUseCase`. Nothing in the app enforces that today, so the test
     * turns the silent N+1 into a thrown exception.
     */
    public function test_it_builds_the_payload_without_lazy_loading(): void
    {
        $user = User::factory()->create();
        $this->homeBar($user);

        Model::preventLazyLoading();

        $this->actingAs($user)
            ->getJson(route('bars.index'))
            ->assertOk();
    }

    public function test_it_does_not_return_another_users_bar(): void
    {
        $user = User::factory()->create();
        $this->homeBar($user);

        $other = User::factory()->create();
        $otherBar = Bar::create(['user_id' => $other->id, 'name' => 'Someone Elses Bar']);
        $syrup = $this->category('syrup', 'Syrup', IngredientType::Syrup, MeasurementUnit::Millilitre, isAlcoholic: false);
        $this->stocked($otherBar, $this->product($syrup, 'gomme', 'Gomme Syrup'), amount: 500, bottleSize: 700);

        $payload = $this->actingAs($user)
            ->getJson(route('bars.index'))
            ->assertOk()
            ->json();

        $this->assertSame('Home Bar', $payload['name']);
        $this->assertNotContains('syrup', array_column($payload['categories'], 'type'));
    }

    /**
     * Gin and Vodka are deliberately two categories of one type, so a grouping
     * that merged on the category rather than the type could not pass. The
     * three units are the cases `BarStock`'s docblock calls out.
     */
    protected function homeBar(User $user): Bar
    {
        $bar = Bar::create(['user_id' => $user->id, 'name' => 'Home Bar']);

        $gin = $this->category('gin', 'Gin', IngredientType::Spirit, MeasurementUnit::Millilitre);
        $vodka = $this->category('vodka', 'Vodka', IngredientType::Spirit, MeasurementUnit::Millilitre);
        $bitters = $this->category('bitters', 'Bitters', IngredientType::Bitters, MeasurementUnit::Dash);
        $citrus = $this->category('citrus', 'Citrus', IngredientType::Fresh, MeasurementUnit::Count, isAlcoholic: false);

        $this->stocked($bar, $this->product($gin, 'plymouth-gin', 'Plymouth Gin'), amount: 400, bottleSize: 700);
        $this->stocked($bar, $this->product($gin, 'london-dry-gin', 'London Dry Gin'), amount: 520, bottleSize: 700);
        $this->stocked($bar, $this->product($vodka, 'plain-vodka', 'Plain Vodka'), amount: 300, bottleSize: 700);
        $this->stocked($bar, $this->product($bitters, 'angostura', 'Angostura Bitters'), amount: 60, bottleSize: 100);
        $this->stocked($bar, $this->product($citrus, 'lime', 'Lime'), amount: 3);

        return $bar;
    }

    /**
     * A curated category — the row a recipe line names when any bottle beneath
     * it will do. Curated rows carry a null creator, which is what the partial
     * unique indexes on `slug` and `path` are scoped to.
     */
    protected function category(
        string $slug,
        string $name,
        IngredientType $type,
        MeasurementUnit $unit,
        bool $isAlcoholic = true,
    ): Ingredient {
        return Ingredient::create([
            'slug' => $slug,
            'name' => $name,
            'kind' => IngredientKind::Category,
            'type' => $type,
            'is_alcoholic' => $isAlcoholic,
            'default_unit' => $unit,
        ]);
    }

    /**
     * A bottle beneath a category. Type, unit and alcohol come from the parent
     * rather than the caller, the same inheritance `IngredientSeeder` applies
     * when it spreads a category over its products: a product that disagrees
     * with its category about its unit is a row the seeder can never produce,
     * so a test built that way would prove nothing. `path` is not assigned
     * here either — the model's saving hook derives it from the parent.
     */
    protected function product(Ingredient $category, string $slug, string $name): Ingredient
    {
        return Ingredient::create([
            'parent_id' => $category->id,
            'slug' => $slug,
            'name' => $name,
            'kind' => IngredientKind::Product,
            'type' => $category->type,
            'is_alcoholic' => $category->is_alcoholic,
            'default_unit' => $category->default_unit,
        ]);
    }

    /**
     * One bottle on one bar's shelf. The unit is the ingredient's, so `amount`
     * reads as millilitres for the gin, dashes for the bitters and whole fruit
     * for the lime — the three cases `BarStock`'s docblock calls out.
     */
    protected function stocked(Bar $bar, Ingredient $ingredient, int $amount, ?int $bottleSize = null): BarStock
    {
        return $bar->stock()->create([
            'ingredient_id' => $ingredient->id,
            'amount' => $amount,
            'bottle_size' => $bottleSize,
        ]);
    }
}
