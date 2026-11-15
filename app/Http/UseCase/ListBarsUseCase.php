<?php

namespace App\Http\UseCase;

use App\DTO\BarList\BarListData;
use App\Models\Bar;

class ListBarsUseCase
{
    public function handle(): BarListData
    {
        $user = auth()->user()->id;

        $bars = Bar::query()->with([
            'stock',
            'stock.ingredient',
            'stock.ingredient.parent',
        ])->where('user_id', '=', $user)->first();

        return BarListData::from($bars);
    }
}
