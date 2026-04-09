<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['admin', 'employee', 'client', 'jte'] as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }

        User::query()
            ->get(['id', 'user_type'])
            ->each(function (User $user): void {
                $role = $user->sidebarRoleName();

                if ($role === null) {
                    return;
                }

                $user->syncRoles([$role]);
            });
    }

    public function down(): void
    {
        // Intentionally left blank.
    }
};
