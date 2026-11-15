<?php

namespace App\DTO\BarList;

use App\Models\BarStock;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class StockCategoryData extends Data
{
    public function __construct(
        public int $id,
        public string $type,
        public string $name,
        /** @var Collection<int, StockItemData> */
        public Collection $items,
    ) {}

    /**
     * @param  Collection<int, BarStock>  $barStock
     * @return Collection<int, self>
     */
    public static function groupByCategory(Collection $barStock): Collection
    {
        return $barStock
            ->groupBy(fn (BarStock $stock): string => $stock->ingredient->type->value)
            ->sortKeys()
            ->map(function (Collection $stockGroup, string $type): self {
                $first = $stockGroup->first();

                return new self(
                    id: $first->ingredient->id,
                    type: $type,
                    name: $first->ingredient->type->label(),
                    items: StockItemData::collect($stockGroup->sortBy('ingredient.name')->values()),
                );
            })
            ->values();
    }
}
