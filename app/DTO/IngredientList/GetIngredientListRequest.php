<?php

namespace App\DTO\IngredientList;


use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;

class GetIngredientListRequest extends Data {
    public function __construct(
        #[Min(2), Max(100)]
        public string $search,
        #[Between(1, 50)]
        public int $limit = 25,
        public ?string $cursor
    )
    {
    }

    public function likeTerm(): string {
        return '%'.self::escapeLike($this->search).'%';
    }

    public function prefixTerm(): string {
        return self::escapeLike($this->search);
    }

    private static function escapeLike(string $value): string {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($value));
    }
}
