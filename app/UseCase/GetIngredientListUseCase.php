<?php

namespace App\UseCase;

use App\Cocktails\Path;
use App\DTO\IngredientList\GetIngredientListRequest;
use App\DTO\IngredientList\GetIngredientListResponse;
use App\DTO\PagedResponse;
use App\Models\Ingredient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class GetIngredientListUseCase
{
    public function handle(GetIngredientListRequest $getIngredientListRequest): JsonResponse
    {
        $likeTerm = $getIngredientListRequest->likeTerm();
        $user = auth()->user();
        $pathsAliasMatching = Ingredient::query()->visibleTo($user)->matching($likeTerm)->orderBy('id')->limit(50)->pluck('path');
        $roots = $pathsAliasMatching
            ->sortBy(fn (Path $path) => strlen($path->value))
            ->reduce(
                fn (Collection $kept, Path $path) =>
                $kept->contains(fn (Path $root) => $root->covers($path))
                    ? $kept
                    : $kept->push($path),
                collect(),
            );
        $pathMatching = Ingredient::query()->underAny($roots)->product()
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
