<?php

use App\Enums\CategoryType;
use App\Enums\IngredientType;
use App\Enums\IngredientUnit;
use App\Models\Ingredient;
use Database\Seeders\IngredientSeeder;
use Illuminate\Support\Str;

test('the default state is a top level category', function () {
    $ingredient = Ingredient::factory()->create();

    expect($ingredient->type)->toBe(IngredientType::Category)
        ->and($ingredient->parent_id)->toBeNull()
        ->and($ingredient->bottle_size_ml)->toBeNull()
        ->and($ingredient->slug)->toBe(Str::slug($ingredient->name))
        ->and($ingredient->isCategory())->toBeTrue()
        ->and($ingredient->category_type)->toBeInstanceOf(CategoryType::class);
});

test('the product state hangs a product off a generated category', function () {
    $product = Ingredient::factory()->product()->create();

    expect($product->type)->toBe(IngredientType::Product)
        ->and($product->isProduct())->toBeTrue()
        ->and($product->bottle_size_ml)->toBeGreaterThan(0)
        ->and($product->category_type)->toBeNull()
        ->and($product->category)->not->toBeNull()
        ->and($product->category?->type)->toBe(IngredientType::Category);
});

test('the categoryType state names a category from that grouping', function () {
    $spirit = Ingredient::factory()->categoryType(CategoryType::Spirit)->create();
    $syrup = Ingredient::factory()->categoryType(CategoryType::Syrup)->create();

    expect($spirit->category_type)->toBe(CategoryType::Spirit)
        ->and($spirit->isCategory())->toBeTrue()
        ->and($syrup->category_type)->toBe(CategoryType::Syrup);
});

test('ofCategoryType filters categories by one grouping or several', function () {
    Ingredient::factory()->count(3)->categoryType(CategoryType::Spirit)->create();
    Ingredient::factory()->count(2)->categoryType(CategoryType::Syrup)->create();
    Ingredient::factory()->categoryType(CategoryType::Mixer)->create();

    expect(Ingredient::ofCategoryType(CategoryType::Spirit)->count())->toBe(3)
        ->and(Ingredient::ofCategoryType([CategoryType::Syrup, CategoryType::Mixer])->count())->toBe(3)
        ->and(Ingredient::ofCategoryType(CategoryType::Bitters)->count())->toBe(0);
});

test('underCategoryType filters stock down to one grouping', function () {
    $gin = Ingredient::factory()->categoryType(CategoryType::Spirit)->create();
    $syrup = Ingredient::factory()->categoryType(CategoryType::Syrup)->create();

    Ingredient::factory()->count(2)->asProduct()->under($gin)->create();
    Ingredient::factory()->asProduct()->under($syrup)->create();

    $spiritsInTheBar = Ingredient::ofType(IngredientType::Product)
        ->underCategoryType(CategoryType::Spirit)
        ->get();

    expect($spiritsInTheBar)->toHaveCount(2)
        ->and($spiritsInTheBar->pluck('parent_id')->unique()->all())->toBe([$gin->id]);
});

test('the product state hangs a product off a given category', function () {
    $gin = Ingredient::factory()->category()->create(['name' => 'Gin', 'slug' => 'gin']);

    $product = Ingredient::factory()->product($gin)->create();

    expect($product->parent_id)->toBe($gin->id)
        ->and(Ingredient::ofType(IngredientType::Category)->count())->toBe(1)
        ->and($gin->products()->count())->toBe(1);
});

test('withProducts puts products under the category', function () {
    $category = Ingredient::factory()->withProducts(3)->create();

    expect($category->isCategory())->toBeTrue()
        ->and($category->products)->toHaveCount(3)
        ->and(Ingredient::ofType(IngredientType::Product)->count())->toBe(3);

    $category->products->each(function (Ingredient $product) use ($category) {
        expect($product->type)->toBe(IngredientType::Product)
            ->and($product->parent_id)->toBe($category->id);
    });
});

test('the products relation excludes child categories', function () {
    $category = Ingredient::factory()->create();
    Ingredient::factory()->asProduct()->under($category)->create();
    Ingredient::factory()->category()->under($category)->create();

    expect($category->products()->count())->toBe(1);
});

test('generated slugs stay unique across many rows', function () {
    $categories = Ingredient::factory()->count(12)->create();
    $products = Ingredient::factory()->count(12)->asProduct()->create();

    expect($categories->pluck('slug')->unique())->toHaveCount(12)
        ->and($products->pluck('slug')->unique())->toHaveCount(12);
});

test('unit states are persisted and cast back to the enum', function () {
    $countable = Ingredient::factory()->countable()->create();
    $dashed = Ingredient::factory()->dashed()->create();
    $explicit = Ingredient::factory()->unit(IngredientUnit::Millilitres)->create();

    expect($countable->fresh()?->unit)->toBe(IngredientUnit::Count)
        ->and($countable->bottle_size_ml)->toBeNull()
        ->and($dashed->fresh()?->unit)->toBe(IngredientUnit::Dash)
        ->and($explicit->fresh()?->unit)->toBe(IngredientUnit::Millilitres);
});

test('the substitute group state is persisted', function () {
    $ingredient = Ingredient::factory()->substituteGroup('Gin (any)')->create();

    expect($ingredient->fresh()?->substitute_group)->toBe('Gin (any)');
});

test('deleting a category deletes its products', function () {
    $category = Ingredient::factory()->withProducts(2)->create();

    $category->delete();

    expect(Ingredient::count())->toBe(0);
});

test('the seeder lays down categories with products under them', function () {
    $this->seed(IngredientSeeder::class);

    $categories = Ingredient::ofType(IngredientType::Category)->get();
    $products = Ingredient::ofType(IngredientType::Product)->get();

    expect($categories)->not->toBeEmpty()
        ->and($products)->not->toBeEmpty()
        ->and($categories->whereNotNull('parent_id'))->toBeEmpty()
        ->and($products->whereNull('parent_id'))->toBeEmpty();

    $gin = Ingredient::where('slug', 'gin')->firstOrFail();

    expect($gin->type)->toBe(IngredientType::Category)
        ->and($gin->category_type)->toBe(CategoryType::Spirit)
        ->and($gin->unit)->toBe(IngredientUnit::Millilitres)
        ->and($gin->substitute_group)->toBe('Gin (any)')
        ->and($gin->products->pluck('name'))->toContain('Tanqueray London Dry');
});

test('every seeded category carries a grouping and no product does', function () {
    $this->seed(IngredientSeeder::class);

    $categories = Ingredient::ofType(IngredientType::Category)->get();
    $products = Ingredient::ofType(IngredientType::Product)->get();

    expect($categories->whereNull('category_type'))->toBeEmpty()
        ->and($products->whereNotNull('category_type'))->toBeEmpty()
        ->and($categories->pluck('category_type')->unique())
        ->toHaveCount(count(CategoryType::cases()));
});

test('the seeded bar can be grouped by spirits', function () {
    $this->seed(IngredientSeeder::class);

    $spirits = Ingredient::ofType(IngredientType::Product)
        ->underCategoryType(CategoryType::Spirit)
        ->with('category')
        ->get();

    expect($spirits)->not->toBeEmpty()
        ->and($spirits->pluck('category.name')->unique())->toContain('Gin')
        ->and($spirits->pluck('name'))->not->toContain('Giffard Orgeat');
});

test('the seeder is idempotent', function () {
    $this->seed(IngredientSeeder::class);
    $first = Ingredient::count();

    $this->seed(IngredientSeeder::class);

    expect(Ingredient::count())->toBe($first);
});
