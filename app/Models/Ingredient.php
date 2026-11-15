<?php

namespace App\Models;

use App\Enums\CategoryType;
use App\Enums\IngredientType;
use App\Enums\IngredientUnit;
use Database\Factories\IngredientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property IngredientType $type
 * @property CategoryType|null $category_type
 * @property string $name
 * @property string $slug
 * @property IngredientUnit $unit
 * @property string|null $substitute_group
 * @property int|null $bottle_size_ml
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Ingredient|null $category
 * @property-read Collection<int, Ingredient> $products
 */
#[Fillable(['parent_id', 'type', 'category_type', 'name', 'slug', 'unit', 'substitute_group', 'bottle_size_ml'])]
class Ingredient extends Model
{
    /** @use HasFactory<IngredientFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => IngredientType::class,
            'category_type' => CategoryType::class,
            'unit' => IngredientUnit::class,
            'bottle_size_ml' => 'integer',
        ];
    }

    /**
     * The category a product hangs off. Null on a category row.
     *
     * @return BelongsTo<Ingredient, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * The stockable products under a category.
     *
     * @return HasMany<Ingredient, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->where('type', IngredientType::Product);
    }

    /**
     * @param  Builder<Ingredient>  $query
     */
    public function scopeOfType(Builder $query, IngredientType $type): void
    {
        $query->where('type', $type);
    }

    /**
     * Categories in one of the given groupings — the Bar screen's kickers and
     * the base-spirit filters.
     *
     * @param  Builder<Ingredient>  $query
     * @param  CategoryType|list<CategoryType>  $categoryType
     */
    public function scopeOfCategoryType(Builder $query, CategoryType|array $categoryType): void
    {
        $query->whereIn('category_type', is_array($categoryType) ? $categoryType : [$categoryType]);
    }

    /**
     * Products whose category is in one of the given groupings.
     *
     * @param  Builder<Ingredient>  $query
     * @param  CategoryType|list<CategoryType>  $categoryType
     */
    public function scopeUnderCategoryType(Builder $query, CategoryType|array $categoryType): void
    {
        $types = is_array($categoryType) ? $categoryType : [$categoryType];

        $query->whereHas('category', function (Builder $categoryQuery) use ($types): void {
            $categoryQuery->whereIn('category_type', $types);
        });
    }

    public function isCategory(): bool
    {
        return $this->type === IngredientType::Category;
    }

    public function isProduct(): bool
    {
        return $this->type === IngredientType::Product;
    }
}
