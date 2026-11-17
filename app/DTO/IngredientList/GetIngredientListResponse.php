<?php

namespace App\DTO\IngredientList;


use App\Models\Ingredient;
use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;

class GetIngredientListResponse extends Data {
    public function __construct(
        public string $id,
        public string $label,
        public ?string $subLabel,
    )
    {
    }

    public static function fromModel(Ingredient $ingredient): self
    {
        return new self (
            (string) $ingredient->id,
            label: $ingredient->name,
            subLabel: $ingredient->parent_name,
        );
    }
}
