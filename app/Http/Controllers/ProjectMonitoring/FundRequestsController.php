<?php

namespace App\Http\Controllers\ProjectMonitoring;

use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class FundRequestsController extends TableCrudController
{
    // Gunakan Eloquent Model
    protected string $model = \App\Models\FundRequest::class;

    protected function storeRules(): array
    {
        // Paksa 'requested_by' memakai ID user yang sedang login
        request()->merge([
            'requested_by' => auth()->Auth::user()->id,
            'status' => 'pending' // Paksa status awal selalu pending
        ]);

        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'requested_by' => ['required', 'integer', 'exists:users,id'],
            // Amount wajib diisi (tidak boleh nullable jika minta uang)
            'amount' => ['required', 'numeric', 'min:1'],
            'status' => ['required', Rule::in(['pending', 'approved_manager', 'approved_finance', 'rejected'])],
        ];
    }

    protected function updateRules(int $id): array
    {
        $rules = [
            'project_id' => ['sometimes', 'required', 'integer', 'exists:projects,id'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:1'],
        ];

        // LOGIKA SPATIE RBAC: 
        // Cek apakah user yang login punya role Management atau Finance
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user && $user->hasAnyRole(['Management', 'Finance Staff'])) {
            $rules['status'] = [
                'sometimes',
                'required',
                Rule::in(['pending', 'approved_manager', 'approved_finance', 'rejected'])
            ];
        } else {
            if (request()->has('status')) {
                throw ValidationException::withMessages([
                    'status' => 'Anda tidak memiliki hak akses untuk menyetujui atau mengubah status pengajuan ini.'
                ]);
            }
        }

        return $rules;
    }
}
