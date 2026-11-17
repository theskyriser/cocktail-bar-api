<?php

namespace App\DTO;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\AbstractCursorPaginator;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\Paginator;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Resource;

final class PagedResponse
{
    /**
     * @param  class-string<Data|Resource>  $dataClass
     */
    public static function make(CursorPaginator|Paginator $page, string $dataClass): JsonResponse
    {
        $nextCursor = $page instanceof CursorPaginator
            ? $page->nextCursor()?->encode()
            : ($page->hasMorePages() ? (string) ($page->currentPage() + 1) : null);

        return response()->json([
            'data' => $dataClass::collect($page->items()),
            'meta' => ['nextCursor' => $nextCursor],
        ]);
    }
}
