<?php

namespace Tests\Feature;

use App\Cocktails\Path;
use App\Enums\IngredientKind;
use App\Models\BarStock;
use App\Models\Ingredient;
use App\Models\User;
use Database\Seeders\BarSeeder;
use Database\Seeders\BarStockSeeder;
use Database\Seeders\IngredientSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngredientSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IngredientSeeder::class);
    }

    public function test_it_seeds_all_three_kinds(): void
    {
        foreach (IngredientKind::cases() as $kind) {
            $this->assertGreaterThan(
                0,
                Ingredient::query()->where('kind', $kind)->count(),
                "No rows seeded for kind [{$kind->value}].",
            );
        }
    }

    public function test_every_product_is_a_brand_hanging_off_something_broader(): void
    {
        $orphans = Ingredient::query()
            ->where('kind', IngredientKind::Product)
            ->whereNull('parent_id')
            ->pluck('slug');

        $this->assertSame([], $orphans->all());
    }

    public function test_no_product_has_anything_beneath_it(): void
    {
        $brands = Ingredient::query()->where('kind', IngredientKind::Product)->pluck('id');

        $this->assertSame(
            [],
            Ingredient::query()->whereIn('parent_id', $brands)->pluck('slug')->all(),
        );
    }

    public function test_every_style_sits_directly_under_a_category(): void
    {
        $styles = Ingredient::query()->where('kind', IngredientKind::Style)->with('parent')->get();

        $this->assertNotEmpty($styles);

        foreach ($styles as $style) {
            $this->assertSame(
                IngredientKind::Category,
                $style->parent?->kind,
                "Style [{$style->slug}] does not sit under a category.",
            );
        }
    }

    public function test_every_category_is_a_root(): void
    {
        $this->assertSame(
            [],
            Ingredient::query()
                ->where('kind', IngredientKind::Category)
                ->whereNotNull('parent_id')
                ->pluck('slug')
                ->all(),
        );
    }

    public function test_a_child_inherits_its_parents_type_alcohol_and_unit(): void
    {
        foreach (Ingredient::query()->whereNotNull('parent_id')->with('parent')->get() as $child) {
            $parent = $child->parent;
            $this->assertInstanceOf(Ingredient::class, $parent);

            $this->assertSame($parent->type, $child->type, "Type drifted at [{$child->slug}].");
            $this->assertSame($parent->is_alcoholic, $child->is_alcoholic, "Alcohol drifted at [{$child->slug}].");
            $this->assertSame($parent->default_unit, $child->default_unit, "Unit drifted at [{$child->slug}].");
        }
    }

    public function test_filtering_on_whiskey_reaches_its_styles_and_their_brands(): void
    {
        $whiskey = Ingredient::query()->where('slug', 'whiskey')->sole();

        $slugs = Ingredient::query()->within($whiskey->path)->pluck('slug');

        $this->assertTrue($slugs->contains('whiskey'));
        $this->assertTrue($slugs->contains('bourbon'));
        $this->assertTrue($slugs->contains('buffalo-trace'));
        $this->assertTrue($slugs->contains('rye'));
        $this->assertFalse($slugs->contains('gin'));
    }

    public function test_a_brand_sits_inside_its_styles_subtree(): void
    {
        $bourbon = Ingredient::query()->where('slug', 'bourbon')->sole();
        $buffaloTrace = Ingredient::query()->where('slug', 'buffalo-trace')->sole();

        $this->assertSame('whiskey.bourbon', $bourbon->path->value);
        $this->assertSame('whiskey.bourbon.buffalo-trace', $buffaloTrace->path->value);
        $this->assertTrue($bourbon->path->covers($buffaloTrace->path));
        $this->assertTrue(Path::root('whiskey')->covers($buffaloTrace->path));
    }

    public function test_a_brand_may_hang_straight_off_a_category_where_no_style_sits_between(): void
    {
        $plymouth = Ingredient::query()->where('slug', 'plymouth-gin')->sole();

        $this->assertSame(IngredientKind::Product, $plymouth->kind);
        $this->assertSame(IngredientKind::Category, $plymouth->parent?->kind);
        $this->assertSame('gin.plymouth-gin', $plymouth->path->value);
    }

    public function test_a_style_with_no_brand_stays_the_most_specific_row_there_is(): void
    {
        $lime = Ingredient::query()->where('slug', 'lime')->sole();

        $this->assertSame(IngredientKind::Style, $lime->kind);
        $this->assertSame(0, $lime->children()->count());
    }

    public function test_it_keeps_the_slugs_the_other_seeders_name(): void
    {
        $required = [
            'gin', 'bourbon', 'rye', 'white-rum', 'blanco-tequila', 'sweet-vermouth',
            'triple-sec', 'campari', 'angostura', 'lime-juice', 'lemon-juice',
            'simple-syrup', 'soda-water', 'tonic-water', 'mint', 'orange-peel',
            'lime-wheel', 'cocktail-cherry',
        ];

        $missing = array_values(array_diff(
            $required,
            Ingredient::query()->pluck('slug')->all(),
        ));

        $this->assertSame([], $missing);
    }

    public function test_the_seeded_shelf_holds_a_brand_wherever_the_catalogue_has_one(): void
    {
        User::factory()->create();

        $this->seed(BarSeeder::class);
        $this->seed(BarStockSeeder::class);

        $stocked = Ingredient::query()
            ->whereIn('id', BarStock::query()->pluck('ingredient_id'))
            ->get();

        $this->assertNotEmpty($stocked);

        foreach ($stocked as $ingredient) {
            if ($ingredient->kind === IngredientKind::Product) {
                continue;
            }

            $this->assertSame(
                0,
                $ingredient->children()->count(),
                "Stocked [{$ingredient->slug}] is not a brand but has brands beneath it.",
            );
        }
    }
}
