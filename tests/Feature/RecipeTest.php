<?php

namespace Tests\Feature;

use App\Models\Glass;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_recipe_with_a_glass_and_a_creator(): void
    {
        $user = User::factory()->create();
        $glass = Glass::factory()->create();

        $recipe = Recipe::factory()->createdBy($user)->servedIn($glass)->create([
            'name' => 'Negroni',
            'description' => 'Equal parts, stirred.',
            'method' => 'Stir with ice. Strain over a large cube.',
        ]);

        $this->assertSame('Negroni', $recipe->fresh()->name);
        $this->assertSame($glass->id, $recipe->glass->id);
        $this->assertSame($user->id, $recipe->creator->id);
    }

    public function test_it_treats_a_null_creator_as_curated_and_shows_it_to_everyone(): void
    {
        $mine = User::factory()->create();
        $other = User::factory()->create();

        Recipe::factory()->create(['name' => 'Classic Daiquiri']);
        Recipe::factory()->createdBy($mine)->create(['name' => 'My Own']);
        Recipe::factory()->createdBy($other)->create(['name' => 'Someone Elses']);

        $this->assertSame(['Classic Daiquiri'], Recipe::curated()->pluck('name')->all());
        $this->assertEqualsCanonicalizing(
            ['Classic Daiquiri', 'My Own'],
            Recipe::visibleTo($mine)->pluck('name')->all(),
        );
        $this->assertSame(['Classic Daiquiri'], Recipe::visibleTo(null)->pluck('name')->all());
    }

    public function test_it_nulls_the_glass_but_keeps_the_recipe_when_a_glass_is_deleted(): void
    {
        $glass = Glass::factory()->create();
        $recipe = Recipe::factory()->servedIn($glass)->create();

        $glass->delete();

        $this->assertNotNull($recipe->fresh());
        $this->assertNull($recipe->fresh()->glass_id);
    }
}
