<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

abstract class Controller
{
    protected const DEFAULT_PER_PAGE = 15;
    protected const MAX_PER_PAGE = 100;
    protected const PER_PAGE_OPTIONS = [10, 15, 25, 50, 100];

    protected function perPageFromRequest(Request $request): int
    {
        $requested = $request->integer('per_page', static::DEFAULT_PER_PAGE);

        if ($requested < 1) {
            return static::DEFAULT_PER_PAGE;
        }

        if (in_array($requested, static::PER_PAGE_OPTIONS, true)) {
            return $requested;
        }

        if ($requested > static::MAX_PER_PAGE) {
            return static::MAX_PER_PAGE;
        }

        return static::DEFAULT_PER_PAGE;
    }

    protected function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'perPage' => $paginator->perPage(),
            'total' => $paginator->total(),
            'perPageOptions' => static::PER_PAGE_OPTIONS,
            'maxPerPage' => static::MAX_PER_PAGE,
        ];
    }
}
