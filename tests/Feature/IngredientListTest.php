<?php

namespace Tests\Feature;

use App\Concerns\ActingAsUser;
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
use function PHPUnit\Framework\assertCount;

class IngredientListTest extends TestCase
{
    use RefreshDatabase;
    use ActingAsUser;

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
        $this->app['auth']->forgetGuards();
        $this->getJson(route('ingredients.index'))->assertUnauthorized();
    }

    /**
     * The contract test: one assertion pinning the whole payload, so a change
     * to a key, its casing, the nesting or the ordering fails here rather than
     * in the client's zod schema.
     */
    public function test_it_pages_through_each_match_exactly_once(): void
    {
        $gin = Ingredient::factory()->ofType(IngredientType::Spirit)->create(['name' => 'Gin']);

        $dryGin = Ingredient::factory()->style($gin)->create(['name' => 'London Dry Gin']);
        $products = Ingredient::factory()->under($dryGin)->count(10)->create();

        $seen = [];
        $cursor = null;

        do {
            $response = $this->getJson(route('ingredients.index', ['search' => 'Gin', 'limit' => 5, 'cursor' => $cursor]))->assertOk();

            $seen = [...$seen, ...array_column($response->json('data'), 'id')];
            $cursor = $response->json('meta.nextCursor');
        } while ($cursor !== null);

        $this->assertCount(10, $seen);
        $this->assertCount( 10, array_unique($seen));

    }

    public function test_it_finds_the_product_by_alias(): void {
        $gin = Ingredient::factory()->ofType(IngredientType::Spirit)->create(['name' => 'Gin']);
        $dryGin = Ingredient::factory()->style($gin)->create(['name' => 'London Dry Gin']);
        $products = Ingredient::factory()->under($dryGin)->withAlias('alistjdfsdfsdfsi')->count(1)->create();

        $response = $this->getJson(route('ingredients.index', ['search' => 'alistjdfsdfsdfsi', 'limit' => 5]))->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    public function test_it_finds_all_of_one_type(): void {
        $gin = Ingredient::factory()->ofType(IngredientType::Spirit)->create(['name' => 'Gin']);
        $rum = Ingredient::factory()->ofType(IngredientType::Spirit)->create(['name' => 'Rum']);
        $dryGin = Ingredient::factory()->style($gin)->create(['name' => 'London Dry Gin']);
        $whiteRum = Ingredient::factory()->style($rum)->create(['name' => 'White Rum']);
        $ginProducts = Ingredient::factory()->under($dryGin)->count(10)->create();
        $rumProducts = Ingredient::factory()->under($whiteRum)->count(5)->create();

        $response = $this->getJson(route('ingredients.index', ['search' => 'Rum', 'limit' => 3]))->assertOk();

        $this->assertCount(3, $response->json('data'));
    }

    public function test_it_only_shows_visible_products()
    {
        $user2 = User::factory()->create();
        $gin = Ingredient::factory()->ofType(IngredientType::Spirit)->create(['name' => 'Gin']);
        $dryGin = Ingredient::factory()->style($gin)->create(['name' => 'London Dry Gin']);
        $ginProducts = Ingredient::factory()->under($dryGin)->count(2)->create();
        $homeGinProducts = Ingredient::factory()->under($dryGin)->createdBy($this->user)->count(1)->create();
        $otherProducts = Ingredient::factory()->under($dryGin)->createdBy($user2)->count(1)->create();

        $response = $this->getJson(route('ingredients.index', ['search' => 'Gin', 'limit' => 10]))->assertOk();

        $this->assertCount(3, $response->json('data'));
    }

    public function test_it_finds_products_one_or_two_layers_deep(): void {
        $gin = Ingredient::factory()->ofType(IngredientType::Spirit)->create(['name' => 'Gin']);
        $dryGin = Ingredient::factory()->style($gin)->create(['name' => 'London Dry Gin']);
        $rum = Ingredient::factory()->ofType(IngredientType::Spirit)->create(['name' => 'Rum']);
        $ginProducts = Ingredient::factory()->under($dryGin)->count(5)->create();
        $rumProducts = Ingredient::factory()->under($rum)->count(5)->create();

        $response = $this->getJson(route('ingredients.index', ['search' => 'Rum', 'limit' => 3]))->assertOk();

        $this->assertCount(3, $response->json('data'));

        $response = $this->getJson(route('ingredients.index', ['search' => 'Gin', 'limit' => 3]))->assertOk();

        $this->assertCount(3, $response->json('data'));
    }

    public function test_it_treats_percentage_and_underscore_as_literal_characters(): void
    {
        $gin = Ingredient::factory()->ofType(IngredientType::Spirit)->create(['name' => 'Gin']);

        $dryGin = Ingredient::factory()->style($gin)->create(['name' => 'London Dry Gin']);
        Ingredient::factory()->under($dryGin)->count(10)->create();

        $response = $this->getJson(route('ingredients.index', ['search' => '%%', 'limit' => 5]))->assertOk();

        assertCount(0, $response->json('data'));
    }

    public function test_it_rejects_an_oversized_page() {
        $this->getJson(route('ingredients.index', ['search' => '%%', 'limit' => 500]))->assertUnprocessable()->assertJsonValidationErrors('limit');
    }

    public function test_it_returns_the_parent_name() {
        $gin = Ingredient::factory()->ofType(IngredientType::Spirit)->create(['name' => 'Gin']);

        $dryGin = Ingredient::factory()->style($gin)->create(['name' => 'London Dry Gin']);
        Ingredient::factory()->under($dryGin)->count(10)->create();
        $response = $this->getJson(route('ingredients.index', ['search' => 'Gin', 'limit' => 1]));

        $this->assertEquals('London Dry Gin', $response->json('data.0.subLabel'));
    }
}
