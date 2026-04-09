<?php

namespace App\Http\Controllers\ProjectMonitoring;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UsersController extends TableCrudController
{
    protected string $table = 'users';

    protected function storeRules(): array
    {
        return [
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'user_type' => ['required', Rule::in(['employee', 'client', 'admin'])],
            'employee_role' => ['nullable', 'string', 'max:50'],
            'email_verified_at' => ['nullable', 'date'],
            'remember_token' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function updateRules(int $id): array
    {
        return [
            'client_id' => ['sometimes', 'nullable', 'integer', 'exists:clients,id'],
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => ['sometimes', 'required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($id)],
            'password' => ['sometimes', 'required', 'string', 'min:8', 'max:255'],
            'user_type' => ['sometimes', 'required', Rule::in(['employee', 'client', 'admin'])],
            'employee_role' => ['sometimes', 'nullable', 'string', 'max:50'],
            'email_verified_at' => ['sometimes', 'nullable', 'date'],
            'remember_token' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }
}
