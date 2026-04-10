<?php

namespace App\Http\Controllers;

use App\Models\Client;
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

class AdminAccMgmtController extends Controller
{
    public function index(): Response
    {
        $this->ensureUserTypeEnum();
        AccessControl::sync();

        $users = User::query()
            ->with('client:id,name')
            ->latest('id')
            ->get(['id', 'client_id', 'name', 'email', 'user_type', 'employee_role', 'email_verified_at', 'created_at'])
            ->map(fn(User $user): array => [
                'id' => $user->id,
                'clientId' => $user->client_id,
                'name' => $user->name,
                'email' => $user->email,
                'userType' => $user->user_type,
                'userTypeLabel' => match ($user->user_type) {
                    'admin' => 'Admin',
                    'employee' => 'Employee',
                    'client' => 'Client',
                    default => ucfirst((string) $user->user_type),
                },
                'employeeRole' => $this->normalizeEmployeeRole($user->employee_role),
                'employeeRoleLabel' => $this->normalizeEmployeeRole($user->employee_role) !== null
                    ? Str::headline($this->normalizeEmployeeRole($user->employee_role))
                    : null,
                'clientName' => $user->client?->name,
                'verifiedAt' => optional($user->email_verified_at)->format('Y-m-d'),
                'createdAt' => optional($user->created_at)->format('Y-m-d'),
            ])
            ->all();

        $stats = [
            'total' => User::query()->count(),
            'admin' => User::query()->where('user_type', 'admin')->count(),
            'employee' => User::query()->where('user_type', 'employee')->count(),
            'client' => User::query()->where('user_type', 'client')->count(),
        ];

        $roles = Role::query()
            ->with('permissions:name')
            ->where('guard_name', 'web')
            ->get()
            ->sortBy(fn(Role $role): array => [
                $role->name === 'admin' ? 0 : 1,
                $role->name === 'employee' ? 0 : 1,
                $role->name === 'client' ? 0 : 1,
                $role->name,
            ])
            ->values()
            ->map(fn(Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => Str::headline($role->name),
                'permissions' => $role->permissions->pluck('name')->sort()->values()->all(),
                'userCount' => User::role($role->name)->count(),
                'isLocked' => $role->name === 'admin',
            ])
            ->all();

        return Inertia::render('AdmnUsrMgmt', [
            'users' => $users,
            'clients' => Client::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn(Client $client): array => [
                    'id' => $client->id,
                    'name' => $client->name,
                ])
                ->all(),
            'stats' => $stats,
            'userTypes' => [
                ['value' => 'admin', 'label' => 'Admin'],
                ['value' => 'employee', 'label' => 'Employee'],
                ['value' => 'client', 'label' => 'Client'],
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

        // Simpan ke variabel $user
        $user = User::create([
            'client_id' => $data['user_type'] === 'client' ? $data['client_id'] : null,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'user_type' => $data['user_type'],
            'employee_role' => $data['user_type'] === 'employee' ? $data['employee_role'] : null,
        ]);

        // Assign Role ke User baru
        if ($data['user_type'] === 'admin') {
            $user->assignRole('admin');
        } elseif ($data['user_type'] === 'employee' && !empty($data['employee_role'])) {
            $user->assignRole($data['employee_role']);
        }

        return to_route('admin.acc_mgmt')->with('success', 'Account created successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureUserTypeEnum();
        $data = $this->validatePayload($request, $user->id, true);

        $user->update([
            'client_id' => $data['user_type'] === 'client' ? $data['client_id'] : null,
            'name' => $data['name'],
            'email' => $data['email'],
            'user_type' => $data['user_type'],
            'employee_role' => $data['user_type'] === 'employee' ? $data['employee_role'] : null,
            ...(! empty($data['password']) ? ['password' => $data['password']] : []),
        ]);

        // Update Role
        if ($data['user_type'] === 'admin') {
            $user->syncRoles(['admin']);
        } elseif ($data['user_type'] === 'employee' && !empty($data['employee_role'])) {
            $user->syncRoles([$data['employee_role']]);
        } else {
            // Jika diubah jadi Client, copot semua role internal
            $user->syncRoles([]); 
        }

        return to_route('admin.acc_mgmt')->with('success', 'Account updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->ensureUserTypeEnum();

        $user->delete();

        return to_route('admin.acc_mgmt')->with('success', 'Account deleted successfully.');
    }

    public function updateRolePermissions(Request $request, Role $role): RedirectResponse
    {
        AccessControl::sync();

        abort_if($role->guard_name !== 'web', 404);
        abort_if($role->name === 'admin', 422, 'The admin role is managed automatically.');

        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(AccessControl::permissionNames())],
        ]);

        $role->syncPermissions(
            AccessControl::expandPermissions($validated['permissions'] ?? []),
        );

        return to_route('admin.acc_mgmt')->with('success', sprintf('Permissions updated for %s.', Str::headline($role->name)));
    }

    protected function validatePayload(Request $request, ?int $userId = null, bool $updating = false): array
    {
        $rules = [
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($userId)],
            'password' => [$updating ? 'nullable' : 'required', 'string', 'min:8', 'max:255'],
            'user_type' => ['required', Rule::in(['admin', 'employee', 'client'])],
            'employee_role' => ['nullable', 'string', 'max:50'],
        ];

        $data = $request->validate($rules);

        if (($data['user_type'] ?? null) !== 'client') {
            $data['client_id'] = null;
        } else {
            $request->validate([
                'client_id' => ['required', 'integer', 'exists:clients,id'],
            ]);
        }

        if (($data['user_type'] ?? null) !== 'employee') {
            $data['employee_role'] = null;
        } else {
            $data['employee_role'] = $this->normalizeEmployeeRole($data['employee_role'] ?? null);
        }

        return $data;
    }

    protected function ensureUserTypeEnum(): void
    {
        if (! Schema::hasColumn('users', 'employee_role')) {
            DB::statement('ALTER TABLE users ADD COLUMN employee_role VARCHAR(50) NULL AFTER user_type');
        }

        $columnType = DB::table('information_schema.columns')
            ->whereRaw('table_schema = DATABASE()')
            ->where('table_name', 'users')
            ->where('column_name', 'user_type')
            ->value('column_type');

        if (! is_string($columnType)) {
            return;
        }

        if (str_contains($columnType, "'admin'") && str_contains($columnType, "'employee'") && ! str_contains($columnType, "'jte'")) {
            return;
        }

        DB::statement("UPDATE users SET user_type = 'employee' WHERE user_type = 'jte'");
        DB::statement("ALTER TABLE users MODIFY user_type ENUM('client','employee','admin') NOT NULL DEFAULT 'client'");
    }

    protected function normalizeEmployeeRole(null|string $role): ?string
    {
        $value = trim((string) $role);

        if ($value === '') {
            return null;
        }

        return Str::slug($value);
    }
}
