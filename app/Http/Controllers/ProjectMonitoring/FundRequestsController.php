<?php

namespace App\Http\Controllers\ProjectMonitoring;

use Illuminate\Validation\Rule;

class FundRequestsController extends TableCrudController
{
    protected string $table = 'fund_requests';

    protected function storeRules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'requested_by' => ['required', 'integer', 'exists:users,id'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(['pending', 'approved_manager', 'approved_finance', 'rejected'])],
        ];
    }

    protected function updateRules(int $id): array
    {
        return [
            'project_id' => ['sometimes', 'required', 'integer', 'exists:projects,id'],
            'requested_by' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', 'nullable', Rule::in(['pending', 'approved_manager', 'approved_finance', 'rejected'])],
        ];
    }
}
