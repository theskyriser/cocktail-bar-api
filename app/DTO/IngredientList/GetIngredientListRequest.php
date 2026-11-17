<?php

namespace App\DTO\IngredientList;


use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;

class GetIngredientListRequest extends Data {
    public function __construct(
        public ?string $cursor,
        #[Min(2), Max(100)]
        public string $search,
        #[Between(1, 50)]
        public int $limit = 25
    )
    {
    }

    /**
     * @return string
     */
    public function likeTerm(): string {
        return '%'.self::escapeLike($this->search).'%';
    }

    /**
     * @return string
     */
    public function prefixTerm(): string {
        return self::escapeLike($this->search);
    }

    /**
     * @param string $value
     * @return string
     */
    private static function escapeLike(string $value): string {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($value));
    }
}
