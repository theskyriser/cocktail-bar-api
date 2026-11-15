<?php

namespace Tests\Feature;

use App\Cocktails\Path;
use App\Enums\IngredientKind;
use App\Enums\IngredientType;
use App\Enums\MeasurementUnit;
use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class IngredientFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_a_curated_category_by_default(): void
    {
        $ingredient = Ingredient::factory()->create();

        $this->assertSame(IngredientKind::Category, $ingredient->kind);
        $this->assertNull($ingredient->parent_id);
        $this->assertNull($ingredient->created_by_user_id);
    }

    public function test_it_derives_the_slug_from_the_name(): void
    {
        $ingredient = Ingredient::factory()->create(['name' => 'Sweet Vermouth']);

        $this->assertSame('sweet-vermouth', $ingredient->slug);
    }

    public function test_it_never_puts_an_underscore_in_a_slug(): void
    {
        $slugs = Ingredient::factory()->count(12)->create()->pluck('slug');

        foreach ($slugs as $slug) {
            $this->assertStringNotContainsString('_', $slug);
        }
    }

    public function test_it_names_a_category_in_a_way_its_type_and_unit_agree_with(): void
    {
        $bitters = Ingredient::factory()->ofType(IngredientType::Bitters)->create();
        $garnish = Ingredient::factory()->ofType(IngredientType::Garnish)->create();
        $juice = Ingredient::factory()->ofType(IngredientType::Juice)->create();

        $this->assertSame(IngredientType::Bitters, $bitters->type);
        $this->assertSame(MeasurementUnit::Dash, $bitters->default_unit);
        $this->assertTrue($bitters->is_alcoholic);

        $this->assertSame(MeasurementUnit::Count, $garnish->default_unit);
        $this->assertFalse($garnish->is_alcoholic);

        $this->assertSame(MeasurementUnit::Millilitre, $juice->default_unit);
        $this->assertFalse($juice->is_alcoholic);
    }

    public function test_it_takes_a_products_type_alcohol_and_unit_from_its_category(): void
    {
        $category = Ingredient::factory()->ofType(IngredientType::Bitters)->create();

        $product = Ingredient::factory()->under($category)->create();

        $this->assertSame(IngredientKind::Product, $product->kind);
        $this->assertSame($category->id, $product->parent_id);
        $this->assertSame($category->type, $product->type);
        $this->assertSame($category->is_alcoholic, $product->is_alcoholic);
        $this->assertSame($category->default_unit, $product->default_unit);
    }

    public function test_it_derives_a_products_path_from_its_category(): void
    {
        $category = Ingredient::factory()->create(['name' => 'Gin']);

        $product = Ingredient::factory()->under($category)->create(['name' => 'Tanqueray']);

        $this->assertSame('gin', $category->path->value);
        $this->assertSame('gin.tanqueray', $product->path->value);
        $this->assertTrue($category->path->covers($product->path));
    }

    public function test_it_generates_one_shared_category_for_products_without_one(): void
    {
        $products = Ingredient::factory()->count(3)->product()->create();

        $this->assertSame([IngredientKind::Product], $products->pluck('kind')->unique()->all());
        $this->assertCount(1, $products->pluck('parent_id')->unique());
        $this->assertSame(1, Ingredient::query()->where('kind', IngredientKind::Category)->count());
    }

    public function test_it_puts_every_product_inside_its_categorys_subtree(): void
    {
        $category = Ingredient::factory()->create();
        Ingredient::factory()->count(2)->under($category)->create();

        $within = Ingredient::query()->within($category->path)->pluck('id');

        $this->assertCount(3, $within);
        $this->assertTrue($within->contains($category->id));
    }

    public function test_it_scopes_an_ingredient_to_one_user(): void
    {
        $user = User::factory()->create();

        $mine = Ingredient::factory()->createdBy($user)->create();
        $curated = Ingredient::factory()->create();

        $this->assertSame($user->id, $mine->created_by_user_id);
        $this->assertSame([$curated->id], Ingredient::query()->curated()->pluck('id')->all());
        $this->assertCount(2, Ingredient::query()->visibleTo($user)->get());
    }

    public function test_it_lets_two_users_each_hold_an_ingredient_of_the_same_slug(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();

        Ingredient::factory()->createdBy($first)->create(['name' => 'Homemade Falernum']);
        Ingredient::factory()->createdBy($second)->create(['name' => 'Homemade Falernum']);

        $this->assertSame(2, Ingredient::query()->where('slug', 'homemade-falernum')->count());
    }

    public function test_it_refuses_a_second_curated_row_on_the_same_slug(): void
    {
        Ingredient::factory()->create(['name' => 'Falernum']);

        $this->expectException(QueryException::class);

        Ingredient::factory()->create(['name' => 'Falernum']);
    }

    public function test_it_keeps_generated_names_and_slugs_unique(): void
    {
        $categories = Ingredient::factory()->count(10)->create();
        $products = Ingredient::factory()->count(10)->under($categories->first())->create();

        $this->assertCount(10, $categories->pluck('slug')->unique());
        $this->assertCount(10, $products->pluck('slug')->unique());
    }

    public function test_it_honours_the_alcoholic_scope(): void
    {
        Ingredient::factory()->count(2)->alcoholic()->create();
        Ingredient::factory()->alcoholic(false)->create();

        $this->assertSame(2, Ingredient::query()->alcoholic()->count());
    }

    public function test_it_overrides_the_unit_without_touching_the_name(): void
    {
        $ingredient = Ingredient::factory()
            ->measuredIn(MeasurementUnit::Dash)
            ->create(['name' => 'Absinthe']);

        $this->assertSame('Absinthe', $ingredient->name);
        $this->assertSame(MeasurementUnit::Dash, $ingredient->default_unit);
    }

    public function test_it_reads_a_generated_subtree_back_through_the_relations(): void
    {
        $category = Ingredient::factory()->create(['name' => 'Rye Whiskey']);
        Ingredient::factory()->under($category)->create(['name' => 'Rittenhouse']);
        Ingredient::factory()->under($category)->create(['name' => 'Sazerac']);

        $this->assertSame(
            ['Rittenhouse', 'Sazerac'],
            $category->children()->orderBy('name')->pluck('name')->all(),
        );
        $this->assertSame('Rye Whiskey', $category->children()->first()->parent->name);
        $this->assertTrue(Path::root('rye-whiskey')->covers($category->children()->first()->path));
    }

    public function test_it_agrees_with_the_slug_helper_the_hand_rolled_tests_use(): void
    {
        $ingredient = Ingredient::factory()->create(['name' => 'Lime Cordial']);

        $this->assertSame(Str::slug('Lime Cordial'), $ingredient->slug);
    }
}
