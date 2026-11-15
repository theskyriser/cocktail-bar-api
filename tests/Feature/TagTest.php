<?php

namespace Tests\Feature;

use App\Models\Recipe;
use App\Models\RecipeTag;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_tags_a_recipe_and_reads_it_back_from_both_sides(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->create(['name' => 'Negroni']);
        $tag = Tag::factory()->ownedBy($user)->create(['name' => 'bitter']);

        $recipe->tags()->attach($tag);

        $this->assertSame(['bitter'], $recipe->tags->pluck('name')->all());
        $this->assertSame(['Negroni'], $tag->recipes->pluck('name')->all());
    }

    public function test_it_stamps_the_join_row_when_a_tag_is_applied(): void
    {
        $recipe = Recipe::factory()->create();
        $tag = Tag::factory()->create();

        $recipe->tags()->attach($tag);

        $this->assertNotNull(RecipeTag::sole()->created_at);
    }

    public function test_it_lets_two_users_each_hold_a_tag_of_the_same_name(): void
    {
        $first = Tag::factory()->ownedBy(User::factory()->create())->create(['name' => 'sour']);
        $second = Tag::factory()->ownedBy(User::factory()->create())->create(['name' => 'sour']);

        $this->assertSame($second->name, $first->name);
        $this->assertNotSame($second->user_id, $first->user_id);
    }

    public function test_it_refuses_the_same_tag_name_twice_for_one_user(): void
    {
        $user = User::factory()->create();
        Tag::factory()->ownedBy($user)->create(['name' => 'sour']);

        $this->expectException(QueryException::class);

        Tag::factory()->ownedBy($user)->create(['name' => 'sour']);
    }

    public function test_it_refuses_to_apply_the_same_tag_to_a_recipe_twice(): void
    {
        $recipe = Recipe::factory()->create();
        $tag = Tag::factory()->create();

        $recipe->tags()->attach($tag);

        $this->expectException(QueryException::class);

        $recipe->tags()->attach($tag);
    }

    public function test_it_drops_the_join_rows_when_a_recipe_or_a_tag_goes(): void
    {
        $recipe = Recipe::factory()->create();
        $tag = Tag::factory()->create();
        $recipe->tags()->attach($tag);

        $recipe->delete();
        $this->assertSame(0, RecipeTag::count());

        $other = Recipe::factory()->create();
        $other->tags()->attach($tag);
        $tag->delete();

        $this->assertSame(0, RecipeTag::count());
        $this->assertNotNull($other->fresh());
    }

    public function test_it_drops_a_users_tags_when_the_user_goes(): void
    {
        $user = User::factory()->create();
        Tag::factory()->ownedBy($user)->create();

        $user->delete();

        $this->assertSame(0, Tag::count());
    }
}
