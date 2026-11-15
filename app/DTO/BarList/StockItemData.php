<?php

namespace App\DTO\BarList;

use App\Enums\MeasurementUnit;
use App\Models\BarStock;
use Spatie\LaravelData\Data;

class StockItemData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public int $amount,
        public ?int $bottle_size,
        public MeasurementUnit $default_unit,
        public ?float $percentage_filled,
    ) {}

    public static function fromBarStock(BarStock $stock)
    {
        $percentage_filled = null;

        if ($stock->bottle_size) {
            $percentage_filled = round($stock->amount / $stock->bottle_size, 2);
        }

        return new self(
            id: $stock->id,
            name: $stock->ingredient->name,
            amount: $stock->amount,
            bottle_size: $stock->bottle_size,
            default_unit: $stock->ingredient->default_unit,
            percentage_filled: $percentage_filled
        );
    }
}
