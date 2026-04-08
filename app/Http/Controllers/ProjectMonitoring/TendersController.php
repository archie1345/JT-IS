<?php

namespace App\Http\Controllers\ProjectMonitoring;

use Illuminate\Validation\Rule;

class TendersController extends TableCrudController
{
    protected string $table = 'tenders';

    protected function storeRules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:200'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(['open', 'submitted', 'won', 'lost'])],
        ];
    }

    protected function updateRules(int $id): array
    {
        return [
            'title' => ['sometimes', 'nullable', 'string', 'max:200'],
            'value' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', 'nullable', Rule::in(['open', 'submitted', 'won', 'lost'])],
        ];
    }
}
