<?php

namespace App\Http\Controllers\ProjectMonitoring;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class FundRequestsController extends CrudResourceController
{

    protected string $model = \App\Models\FundRequest::class;

    protected function indexQuery(Request $request): Builder
    {
        $projectId = $request->integer('project');

        return parent::indexQuery($request)
            ->when($projectId > 0, fn (Builder $query) => $query->where('project_id', $projectId));
    }

    protected function storeRules(): array
    {

        request()->merge([
            'requested_by' => Auth::id(),
            'status' => 'pending'
        ]);

        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'requested_by' => ['required', 'integer', 'exists:users,id'],

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
