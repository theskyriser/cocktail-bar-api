<?php

namespace App\UseCase;

use App\DTO\IngredientList\GetIngredientListRequest;
use App\DTO\IngredientList\GetIngredientListResponse;
use App\DTO\PagedResponse;
use App\Models\Ingredient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class GetIngredientListUseCase
{
    public function handle(GetIngredientListRequest $getIngredientListRequest): JsonResponse
    {
        $likeTerm = $getIngredientListRequest->likeTerm();
        $user = auth()->user();
        $pathsAliasMatching = Ingredient::query()->visibleTo($user)->matching($likeTerm)->orderBy('id')->limit(50)->pluck('path');
        $pathMatching = Ingredient::query()->underAny($pathsAliasMatching)->product()
            ->select('id', 'name')
            ->visibleTo($user)
            ->withParentName()
            ->orderBy('name')
            ->orderBy('id')
            ->simplePaginate(
                perPage: $getIngredientListRequest->limit,
                pageName: 'cursor',
                page: max(1, (int) $getIngredientListRequest->cursor),
            );

        return PagedResponse::make($pathMatching, GetIngredientListResponse::class);
    }
}
