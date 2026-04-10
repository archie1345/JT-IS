<?php

namespace App\Http\Controllers\ProjectMonitoring;

class ClientsController extends TableCrudController
{
    protected string $model = \App\Models\Client::class;

    protected function storeRules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:150'],
            'contact' => ['nullable', 'string', 'max:150'],
        ];
    }

    protected function updateRules(int $id): array
    {
        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'contact' => ['sometimes', 'nullable', 'string', 'max:150'],
        ];
    }
}
