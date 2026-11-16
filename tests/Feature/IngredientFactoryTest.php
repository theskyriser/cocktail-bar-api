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
        $this->assertSame(Str::slug('Sweet Vermouth'), $ingredient->slug);
    }

    public function test_it_never_puts_an_underscore_in_a_slug(): void
    {
        $category = Ingredient::factory()->create();
        $styles = Ingredient::factory()->count(3)->style($category)->create();
        $brands = Ingredient::factory()->count(3)->under($styles->first())->create();

        foreach ($styles->concat($brands)->push($category)->pluck('slug') as $slug) {
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

    public function test_it_puts_a_style_under_a_category_and_inherits_from_it(): void
    {
        $category = Ingredient::factory()->ofType(IngredientType::Bitters)->create();

        $style = Ingredient::factory()->style($category)->create();

        $this->assertSame(IngredientKind::Style, $style->kind);
        $this->assertSame($category->id, $style->parent_id);
        $this->assertSame($category->type, $style->type);
        $this->assertSame($category->is_alcoholic, $style->is_alcoholic);
        $this->assertSame($category->default_unit, $style->default_unit);
    }

    public function test_it_names_a_style_that_matches_its_categorys_type(): void
    {
        $garnishStyle = Ingredient::factory()
            ->style(Ingredient::factory()->ofType(IngredientType::Garnish)->create())
            ->create();

        $this->assertSame(IngredientType::Garnish, $garnishStyle->type);
        $this->assertSame(MeasurementUnit::Count, $garnishStyle->default_unit);
        $this->assertFalse($garnishStyle->is_alcoholic);
    }

    public function test_it_takes_a_brands_type_alcohol_and_unit_from_its_parent(): void
    {
        $style = Ingredient::factory()
            ->style(Ingredient::factory()->ofType(IngredientType::Bitters)->create())
            ->create();

        $brand = Ingredient::factory()->under($style)->create();

        $this->assertSame(IngredientKind::Product, $brand->kind);
        $this->assertSame($style->id, $brand->parent_id);
        $this->assertSame($style->type, $brand->type);
        $this->assertSame($style->is_alcoholic, $brand->is_alcoholic);
        $this->assertSame($style->default_unit, $brand->default_unit);
    }

    public function test_it_builds_the_whole_three_tier_chain_for_a_product(): void
    {
        $brand = Ingredient::factory()->product()->create();

        $style = $brand->parent;
        $this->assertInstanceOf(Ingredient::class, $style);
        $category = $style->parent;
        $this->assertInstanceOf(Ingredient::class, $category);

        $this->assertSame(IngredientKind::Product, $brand->kind);
        $this->assertSame(IngredientKind::Style, $style->kind);
        $this->assertSame(IngredientKind::Category, $category->kind);
        $this->assertNull($category->parent_id);
    }

    public function test_it_derives_the_path_down_the_whole_chain(): void
    {
        $category = Ingredient::factory()->create(['name' => 'Whiskey']);
        $style = Ingredient::factory()->style($category)->create(['name' => 'Bourbon']);
        $brand = Ingredient::factory()->under($style)->create(['name' => 'Buffalo Trace']);

        $this->assertSame('whiskey', $category->path->value);
        $this->assertSame('whiskey.bourbon', $style->path->value);
        $this->assertSame('whiskey.bourbon.buffalo-trace', $brand->path->value);
        $this->assertTrue($category->path->covers($brand->path));
        $this->assertTrue($style->path->covers($brand->path));
    }

    public function test_it_lets_a_brand_hang_straight_off_a_category(): void
    {
        $category = Ingredient::factory()->create(['name' => 'Gin']);

        $brand = Ingredient::factory()->under($category)->create(['name' => 'Plymouth Gin']);

        $this->assertSame(IngredientKind::Product, $brand->kind);
        $this->assertSame('gin.plymouth-gin', $brand->path->value);
    }

    public function test_it_finds_every_descendant_of_a_category_whatever_its_depth(): void
    {
        $category = Ingredient::factory()->create(['name' => 'Whiskey']);
        $style = Ingredient::factory()->style($category)->create();
        Ingredient::factory()->count(2)->under($style)->create();
        Ingredient::factory()->under($category)->create();

        $within = Ingredient::query()->within($category->path)->get();

        $this->assertCount(5, $within);
        $this->assertTrue($within->contains($category->id));
    }

    public function test_it_shares_one_category_across_styles_built_together(): void
    {
        $styles = Ingredient::factory()->count(3)->style()->create();

        $this->assertSame([IngredientKind::Style], $styles->pluck('kind')->unique()->all());
        $this->assertCount(1, $styles->pluck('parent_id')->unique());
        $this->assertSame(1, Ingredient::query()->where('kind', IngredientKind::Category)->count());
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
        $brands = Ingredient::factory()->count(10)->under($categories->first())->create();

        $this->assertCount(10, $categories->pluck('slug')->unique());
        $this->assertCount(10, $brands->pluck('slug')->unique());
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
}
