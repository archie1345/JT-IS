<?php

namespace App\Http\Controllers\ProjectMonitoring;

use App\Models\Tender;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TendersController extends TableCrudController
{
    protected string $model = Tender::class;

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

    protected function inertiaView(): ?string
    {
        return 'Pipeline';
    }

    protected function indexQuery(Request $request): Builder
    {
        return Tender::query()->orderByDesc('id');
    }

    protected function transformRecord(Model $record, Request $request): array
    {
        /** @var Tender $record */
        return [
            'id' => $record->id,
            'title' => $record->title,
            'value' => $record->value !== null ? (float) $record->value : null,
            'status' => $record->status,
            'created_at' => optional($record->created_at)->format('Y-m-d'),
        ];
    }
}
