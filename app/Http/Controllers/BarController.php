<?php

namespace App\Http\Controllers;

use App\DTO\BarList\BarListData;
use App\Http\UseCase\ListBarsUseCase;
use App\Models\Bar;
use Illuminate\Http\Request;

class BarController extends Controller
{
    public function __construct(
        public ListBarsUseCase $listBarsUseCase
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): BarListData
    {
        return $this->listBarsUseCase->handle();
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
    public function show(Bar $bar): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Bar $bar): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Bar $bar): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bar $bar): void
    {
        //
    }
}
