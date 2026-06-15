<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index(): Response
    {
        $this->ensureUserTypeEnum();
        AccessControl::sync();

        $users = User::query()
            ->latest('id')
            ->get(['id', 'name', 'email', 'user_type', 'employee_role', 'email_verified_at', 'created_at'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'userType' => $user->user_type,
                'userTypeLabel' => match ($user->user_type) {
                    'admin' => 'Admin',
                    'employee' => 'Karyawan',
                    default => ucfirst((string) $user->user_type),
                },
                'employeeRole' => $this->normalizeEmployeeRole($user->employee_role),
                'employeeRoleLabel' => $this->normalizeEmployeeRole($user->employee_role) !== null
                    ? Str::headline($this->normalizeEmployeeRole($user->employee_role))
                    : null,
                'verifiedAt' => optional($user->email_verified_at)->format('Y-m-d'),
                'createdAt' => optional($user->created_at)->format('Y-m-d'),
            ])
            ->all();

        $stats = [
            'total' => User::query()->count(),
            'admin' => User::query()->where('user_type', 'admin')->count(),
            'employee' => User::query()->where('user_type', 'employee')->count(),
        ];

        $roles = Role::query()
            ->with('permissions:name')
            ->where('guard_name', 'web')
            ->where('name', '!=', 'admin')
            ->get()
            ->sortBy(fn (Role $role): array => [
                $role->name === 'employee' ? 0 : 1,
                $role->name,
            ])
            ->values()
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => $this->roleDisplayLabel($role->name),
                'permissions' => $role->permissions->pluck('name')->sort()->values()->all(),
                'userCount' => User::role($role->name)->count(),
                'isLocked' => $role->name === 'admin',
            ])
            ->all();

        return Inertia::render('admin/UserManagement', [
            'users' => $users,
            'stats' => $stats,
            'userTypes' => [
                ['value' => 'admin', 'label' => 'Admin'],
                ['value' => 'employee', 'label' => 'Karyawan'],
            ],
            'employeeRoleSuggestions' => AccessControl::employeeRoleSuggestions(),
            'roles' => $roles,
            'permissionGroups' => AccessControl::permissionGroups(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureUserTypeEnum();
        $data = $this->validatePayload($request);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'user_type' => $data['user_type'],
            'employee_role' => $data['user_type'] === 'employee' ? $data['employee_role'] : null,
        ]);

        if ($data['user_type'] === 'admin') {
            $user->assignRole('admin');
        } elseif ($data['user_type'] === 'employee' && ! empty($data['employee_role'])) {
            $user->assignRole($data['employee_role']);
        }

        return to_route('admin.acc_mgmt')->with('success', 'Akun berhasil dibuat.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureUserTypeEnum();
        $data = $this->validatePayload($request, $user->id, true);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'user_type' => $data['user_type'],
            'employee_role' => $data['user_type'] === 'employee' ? $data['employee_role'] : null,
            ...(! empty($data['password']) ? ['password' => $data['password']] : []),
        ]);

        if ($data['user_type'] === 'admin') {
            $user->syncRoles(['admin']);
        } elseif ($data['user_type'] === 'employee' && ! empty($data['employee_role'])) {
            $user->syncRoles([$data['employee_role']]);
        }

        return to_route('admin.acc_mgmt')->with('success', 'Akun berhasil diupdate.');
    }

    public function updateRolePermissions(Request $request, Role $role): RedirectResponse
    {
        AccessControl::sync();

        if ($role->name === 'admin') {
            return to_route('admin.acc_mgmt')->with('success', 'Admin selalu memiliki akses ke semua permission.');
        }

        $data = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(AccessControl::permissionNames())],
        ]);

        AccessControl::replaceRolePermissions(
            $role,
            AccessControl::expandPermissions($data['permissions'] ?? []),
        );

        return to_route('admin.acc_mgmt')->with('success', 'Permission role berhasil diupdate.');
    }

    protected function validatePayload(Request $request, ?int $userId = null, bool $updating = false): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($userId)],
            'password' => [$updating ? 'nullable' : 'required', 'string', 'min:8', 'max:255'],
            'user_type' => ['required', Rule::in(['admin', 'employee'])],
            'employee_role' => ['nullable', 'string', 'max:50'],
        ];

        $data = $request->validate($rules);

        if (($data['user_type'] ?? null) === 'employee') {
            $data['employee_role'] = $this->normalizeEmployeeRole($data['employee_role'] ?? null);
        } else {
            $data['employee_role'] = null;
        }

        return $data;
    }

    protected function ensureUserTypeEnum(): void
    {
        if (! Schema::hasColumn('users', 'employee_role')) {
            DB::statement('ALTER TABLE users ADD COLUMN employee_role VARCHAR(50) NULL AFTER user_type');
        }

        DB::statement("UPDATE users SET user_type = 'employee' WHERE user_type = 'client'");
        DB::statement("ALTER TABLE users MODIFY user_type ENUM('employee','admin') NOT NULL DEFAULT 'employee'");
    }

    protected function normalizeEmployeeRole(?string $role): ?string
    {
        $value = trim((string) $role);
        if ($value === '') {
            return null;
        }

        return Str::slug($value);
    }

    protected function roleDisplayLabel(string $role): string
    {
        return [
            'employee' => 'Karyawan',
            'marketing' => 'Marketing',
            'finance' => 'Finance',
            'operational' => 'Operational',
            'management' => 'Management',
            'procurement' => 'Procurement',
            'hr' => 'HR',
        ][$role] ?? Str::headline($role);
    }
}
