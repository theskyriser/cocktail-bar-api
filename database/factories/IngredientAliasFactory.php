<?php

namespace Database\Factories;

use App\Enums\IngredientKind;
use App\Enums\IngredientType;
use App\Enums\MeasurementUnit;
use App\Models\Ingredient;
use App\Models\IngredientAlias;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<IngredientAlias>
 */
class IngredientAliasFactory extends Factory
{
    /**
     * Define the model's default state: a curated category, the broad group at
     * the root of the tree. `path` is left out — the model's saving hook
     * derives it.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        return [
            'alias' => Str::random(6),
        ];
    }
}
