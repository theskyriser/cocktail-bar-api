<?php

namespace App\DTO\BarList;

use App\Models\Bar;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class BarListData extends Data
{
    public function __construct(
        public string $name,
        /** @var Collection<int, StockCategoryData> $categories */
        public Collection $categories,
    ) {}

    public static function fromModel(Bar $bar): BarListData
    {
        return new self(
            $bar->name,
            StockCategoryData::groupByCategory($bar->stock),
        );
    }
}
