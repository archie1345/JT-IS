<?php

namespace App\Http\Controllers\ProjectMonitoring;

use Illuminate\Validation\Rule;

class ProjectUsersController extends TableCrudController
{
    protected string $table = 'project_users';

    protected function storeRules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => ['required', Rule::in(['manager', 'finance', 'field', 'director', 'client'])],
        ];
    }

    protected function updateRules(int $id): array
    {
        return [
            'project_id' => ['sometimes', 'required', 'integer', 'exists:projects,id'],
            'user_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'role' => ['sometimes', 'required', Rule::in(['manager', 'finance', 'field', 'director', 'client'])],
        ];
    }

    protected function usesTimestamps(): bool
    {
        return false;
    }
}
