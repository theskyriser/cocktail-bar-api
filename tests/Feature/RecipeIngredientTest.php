<?php

namespace Tests\Feature;

use App\Enums\IngredientKind;
use App\Enums\IngredientType;
use App\Enums\MeasurementUnit;
use App\Enums\RecipeItemRole;
use App\Enums\SubstitutionRule;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeIngredientTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_a_recipe_out_of_ordered_measured_lines(): void
    {
        $recipe = Recipe::factory()->create(['name' => 'Negroni']);

        $gin = $this->ingredient('Gin');
        $campari = $this->ingredient('Campari');
        $vermouth = $this->ingredient('Sweet Vermouth');

        $recipe->items()->createMany([
            ['ingredient_id' => $gin->id, 'amount' => 30, 'role' => RecipeItemRole::BaseSpirit, 'sub_rule' => SubstitutionRule::AnyInGroup, 'position' => 1],
            ['ingredient_id' => $campari->id, 'amount' => 30, 'role' => RecipeItemRole::Bitters, 'position' => 2],
            ['ingredient_id' => $vermouth->id, 'amount' => 30, 'role' => RecipeItemRole::Modifier, 'position' => 3],
        ]);

        $this->assertSame([30, 30, 30], $recipe->items()->pluck('amount')->all());
        $this->assertSame(['Gin', 'Campari', 'Sweet Vermouth'], $recipe->ingredients->pluck('name')->all());

        $base = $recipe->items()->first();

        $this->assertSame(RecipeItemRole::BaseSpirit, $base->role);
        $this->assertSame(SubstitutionRule::AnyInGroup, $base->sub_rule);
        $this->assertFalse($base->is_optional);
    }

    public function test_it_defaults_a_line_to_an_exact_required_match(): void
    {
        $recipe = Recipe::factory()->create();

        $line = $recipe->items()->create([
            'ingredient_id' => $this->ingredient('Rye')->id,
            'amount' => 60,
            'role' => RecipeItemRole::BaseSpirit,
            'position' => 1,
        ]);

        $this->assertSame(SubstitutionRule::Exact, $line->fresh()->sub_rule);
        $this->assertFalse($line->fresh()->is_optional);
    }

    public function test_it_allows_an_unmeasured_optional_line_for_a_garnish(): void
    {
        $recipe = Recipe::factory()->create();

        $line = $recipe->items()->create([
            'ingredient_id' => $this->ingredient('Orange Twist', MeasurementUnit::Count)->id,
            'amount' => null,
            'is_optional' => true,
            'role' => RecipeItemRole::Garnish,
            'position' => 9,
        ]);

        $this->assertNull($line->fresh()->amount);
        $this->assertTrue($line->fresh()->is_optional);
    }

    public function test_it_refuses_the_same_ingredient_twice_in_one_recipe(): void
    {
        $recipe = Recipe::factory()->create();
        $gin = $this->ingredient('Gin');

        $recipe->items()->create([
            'ingredient_id' => $gin->id, 'amount' => 30, 'role' => RecipeItemRole::BaseSpirit, 'position' => 1,
        ]);

        $this->expectException(QueryException::class);

        $recipe->items()->create([
            'ingredient_id' => $gin->id, 'amount' => 15, 'role' => RecipeItemRole::Modifier, 'position' => 2,
        ]);
    }

    public function test_it_discards_the_lines_when_the_recipe_goes(): void
    {
        $recipe = Recipe::factory()->create();
        $recipe->items()->create([
            'ingredient_id' => $this->ingredient('Gin')->id, 'amount' => 30, 'role' => RecipeItemRole::BaseSpirit, 'position' => 1,
        ]);

        $recipe->delete();

        $this->assertSame(0, RecipeIngredient::count());
    }

    public function test_it_reads_back_from_the_ingredient_which_recipes_use_it(): void
    {
        $gin = $this->ingredient('Gin');

        foreach (['Negroni', 'Martini'] as $position => $name) {
            Recipe::factory()->create(['name' => $name])->items()->create([
                'ingredient_id' => $gin->id, 'amount' => 30, 'role' => RecipeItemRole::BaseSpirit, 'position' => 1,
            ]);
        }

        $this->assertEqualsCanonicalizing(['Negroni', 'Martini'], $gin->recipes->pluck('name')->all());
        $this->assertSame(30, $gin->recipes->first()->pivot->amount);
    }

    protected function ingredient(string $name, MeasurementUnit $unit = MeasurementUnit::Millilitre): Ingredient
    {
        return Ingredient::create([
            'slug' => str($name)->slug()->value(),
            'name' => $name,
            'kind' => IngredientKind::Product,
            'type' => IngredientType::Spirit,
            'is_alcoholic' => true,
            'default_unit' => $unit,
        ]);
    }
}
