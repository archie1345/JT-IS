<?php

namespace App\Http\Controllers\ProjectMonitoring;

class PaymentsController extends TableCrudController
{
    protected string $table = 'payments';

    protected function storeRules(): array
    {
        return [
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'payment_date' => ['nullable', 'date'],
        ];
    }

    protected function updateRules(int $id): array
    {
        return [
            'invoice_id' => ['sometimes', 'required', 'integer', 'exists:invoices,id'],
            'amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'payment_date' => ['sometimes', 'nullable', 'date'],
        ];
    }

    protected function usesTimestamps(): bool
    {
        return false;
    }
}
