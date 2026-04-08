<?php

namespace App\Http\Controllers\ProjectMonitoring;

use Illuminate\Validation\Rule;

class InvoicesController extends TableCrudController
{
    protected string $table = 'invoices';

    protected function storeRules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'invoice_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['pending', 'paid', 'overdue'])],
        ];
    }

    protected function updateRules(int $id): array
    {
        return [
            'project_id' => ['sometimes', 'required', 'integer', 'exists:projects,id'],
            'amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'invoice_date' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'nullable', Rule::in(['pending', 'paid', 'overdue'])],
        ];
    }
}
