<?php

namespace App\Http\Controllers\ProjectMonitoring;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash; // Tambahkan ini untuk hashing

class UsersController extends TableCrudController
{
    // GANTI $table jadi $model
    protected string $model = \App\Models\User::class;

    protected function storeRules(): array
    {
        return [
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'user_type' => ['required', Rule::in(['employee', 'client', 'admin'])],
            
            // Wajibkan input role dari frontend
            'role' => ['required', 'string', 'exists:roles,name'], 
        ];
    }

    protected function updateRules(int $id): array
    {
        return [
            'client_id' => ['sometimes', 'nullable', 'integer', 'exists:clients,id'],
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => ['sometimes', 'required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($id)],
            'password' => ['nullable', 'string', 'min:8', 'max:255'], // Password dibuat opsional saat update
            'user_type' => ['sometimes', 'required', Rule::in(['employee', 'client', 'admin'])],
            
            // TAMBAHAN SPATIE
            'role' => ['sometimes', 'required', 'string', 'exists:roles,name'],
        ];
    }

    // Eksekusi setelah user dibuat
    protected function afterStore($record, Request $request): void
    {
        // $record sekarang adalah objek dari App\Models\User, jadi assignRole() bisa jalan!
        if ($request->has('role')) {
            $record->assignRole($request->role);
        }
    }

    // Eksekusi setelah user diupdate
    protected function afterUpdate($record, Request $request): void
    {
        if ($request->has('role')) {
            $record->syncRoles([$request->role]);
        }
    }
}