<?php

namespace App\Http\Controllers;

use App\DTO\IngredientList\GetIngredientListRequest;
use App\Models\Ingredient;
use App\UseCase\GetIngredientListUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class IngredientController extends Controller
{
    public function __construct(
        public GetIngredientListUseCase $getIngredientListUseCase,
    )
    {

    }
    /**
     * Display a listing of the resource.
     */
    public function index(GetIngredientListRequest $getIngredientListRequest): JsonResponse
    {
        return $this->getIngredientListUseCase->handle($getIngredientListRequest);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): void
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Ingredient $ingredient): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ingredient $ingredient): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ingredient $ingredient): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ingredient $ingredient): void
    {
        //
    }
}
