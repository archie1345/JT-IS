<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['admin', 'employee', 'client'] as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }

        User::query()
            ->get(['id', 'user_type', 'employee_role'])
            ->each(function (User $user): void {
                $roles = $user->sidebarRoleNames();

                if ($roles === []) {
                    return;
                }

                foreach ($roles as $role) {
                    Role::findOrCreate($role, 'web');
                }

                $user->syncRoles($roles);
            });
    }

    public function down(): void
    {
        // Intentionally left blank.
    }
};
