<?php

namespace App\DTO\BarList;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class StockCategoryData extends Data
{
    public function __construct(
        public int $id,
        public string $type,
        public string $name,
        /** @var Collection<int, StockItemData> */
        public Collection $items

    ) {}

    public static function groupByCategory(Collection $barStock): Collection
    {
        return $barStock->groupBy(function ($item) {
            return $item->ingredient->parent->type;
        })->sortKeys()->map(function (Collection $stockGroup, string $key) {
            $parent = $stockGroup->first()->ingredient->parent;

            $sorted = $stockGroup->sortBy('ingredient.name')->values();

            return new self(
                $parent->id,
                $key,
                $parent->type->label(),
                items: StockItemData::collect($sorted)
            );
        })->values();

    }
}
