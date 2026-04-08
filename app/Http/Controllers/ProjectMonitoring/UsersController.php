<?php

namespace App\Http\Controllers\ProjectMonitoring;

use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class UsersController extends TableCrudController
{
    protected string $model = \App\Models\User::class;

    protected string $inertiaView = 'Admin/Users/Index';

    public function __construct()
    {
        parent::__construct();
        $this->middleware('role:Management');
    }

    protected function storeRules(): array
    {
        return [
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'user_type' => ['nullable', Rule::in(['jte', 'client'])],
            
            // TAMBAHAN UNTUK SPATIE: Validasi input role yang dipilih dari form frontend
            'role' => ['required', 'string', 'exists:roles,name'],
        ];
    }

    protected function updateRules(int $id): array
    {
        return [
            'client_id' => ['sometimes', 'nullable', 'integer', 'exists:clients,id'],
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => ['sometimes', 'required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($id)],
            
            // Password dibuat opsional saat update. Jika kosong, berarti user tidak ganti password.
            'password' => ['nullable', 'string', 'min:8', 'max:255'], 
            'user_type' => ['sometimes', 'nullable', Rule::in(['jte', 'client'])],
            
            // TAMBAHAN UNTUK SPATIE
            'role' => ['sometimes', 'required', 'string', 'exists:roles,name'],
        ];
    }

    // 3. HOOK SPATIE: Dipanggil dari TableCrudController setelah create() berhasil
    protected function afterStore($record, Request $request): void
    {
        if ($request->has('role')) {
            // Karena $record sekarang adalah Eloquent Model User, assignRole bisa jalan!
            $record->assignRole($request->role);
        }
    }

    // 4. HOOK SPATIE: Dipanggil dari TableCrudController setelah update() berhasil
    protected function afterUpdate($record, Request $request): void
    {
        if ($request->has('role')) {
            // syncRoles akan otomatis menghapus jabatan lama dan menggantinya dengan yang baru
            $record->syncRoles([$request->role]);
        }
    }
}