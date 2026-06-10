<?php

namespace App\Http\Controllers\ProjectMonitoring;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class RabsController extends CrudResourceController
{
    protected string $table = 'rabs';

    public function storeForProject(Request $request, Project $project)
    {
        $request->merge(['project_id' => $project->id]);

        return $this->store($request);
    }

    protected function storeRules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'document_number' => ['nullable', 'string', 'max:120'],
            'document_date' => ['nullable', 'date'],
            'total_budget' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function updateRules(int $id): array
    {
        return [
            'project_id' => ['sometimes', 'required', 'integer', 'exists:projects,id'],
            'document_number' => ['sometimes', 'nullable', 'string', 'max:120'],
            'document_date' => ['sometimes', 'nullable', 'date'],
            'total_budget' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }

    protected function prepareForStore(array $validated, Request $request): array
    {
        unset($validated['total_budget']);

        return $validated;
    }

    protected function prepareForUpdate(array $validated, Request $request, Model $record): array
    {
        unset($validated['total_budget']);

        return $validated;
    }
}
