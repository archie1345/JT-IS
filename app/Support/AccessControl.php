<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessControl
{

    public static function permissionGroups(): array
    {
        return [
            [
                'key' => 'sidebar',
                'label' => 'Sidebar Visibility',
                'description' => 'Controls which sidebar sections and shortcuts are visible for a role.',
                'permissions' => [
                    ['name' => 'sidebar.dashboard.view', 'label' => 'Dashboard menu', 'description' => 'Show the dashboard link in the sidebar.'],
                    ['name' => 'sidebar.finance.billing.view', 'label' => 'Billing menu', 'description' => 'Show the billing shortcut in the finance section.'],
                    ['name' => 'sidebar.finance.cost-realization.view', 'label' => 'Cost realization menu', 'description' => 'Show the cost realization shortcut in the finance section.'],
                    ['name' => 'sidebar.marketing.projects.view', 'label' => 'Projects menu', 'description' => 'Show the projects page in the marketing section.'],
                    ['name' => 'sidebar.marketing.reports.view', 'label' => 'Reports menu', 'description' => 'Show the reports or pipeline shortcut in the marketing section.'],
                    ['name' => 'sidebar.operational.rabs.view', 'label' => 'RAB menu', 'description' => 'Show the RAB page in the operational section.'],
                    ['name' => 'sidebar.operational.raps.view', 'label' => 'RAP menu', 'description' => 'Show the RAP page in the operational section.'],
                    ['name' => 'sidebar.operational.progress.view', 'label' => 'Progress update menu', 'description' => 'Show the progress update shortcut in the operational section.'],
                    ['name' => 'sidebar.clients.view', 'label' => 'Clients menu', 'description' => 'Show the clients shortcut in the sidebar.'],
                    ['name' => 'sidebar.admin.users.view', 'label' => 'Admin users menu', 'description' => 'Show the user and permission management shortcut in the sidebar.'],
                    ['name' => 'sidebar.footer.testing.view', 'label' => 'Testing shortcut', 'description' => 'Show the testing page shortcut in the footer.'],
                    ['name' => 'sidebar.footer.accounts.view', 'label' => 'Account management shortcut', 'description' => 'Show the account management shortcut in the footer.'],
                ],
            ],
            [
                'key' => 'pages',
                'label' => 'Page Access',
                'description' => 'Controls who may open each page or module.',
                'permissions' => [
                    ['name' => 'page.dashboard.view', 'label' => 'Open dashboard', 'description' => 'Allow visiting the main dashboard.'],
                    ['name' => 'page.billing-test.view', 'label' => 'Open billing test', 'description' => 'Allow visiting the billing test page.'],
                    ['name' => 'page.projects.view', 'label' => 'Open projects', 'description' => 'Allow visiting the projects list and details.'],
                    ['name' => 'page.rabs.view', 'label' => 'Open RAB', 'description' => 'Allow visiting the RAB list and detail page.'],
                    ['name' => 'page.raps.view', 'label' => 'Open RAP', 'description' => 'Allow visiting the RAP list and detail page.'],
                    ['name' => 'page.pipeline.view', 'label' => 'Open reports pipeline', 'description' => 'Allow visiting the marketing reports or pipeline page.'],
                    ['name' => 'page.clients.view', 'label' => 'Open clients', 'description' => 'Allow visiting client pages.'],
                    ['name' => 'page.fund-requests.view', 'label' => 'Open fund requests', 'description' => 'Allow visiting the fund request module.'],
                    ['name' => 'page.invoices.view', 'label' => 'Open invoices', 'description' => 'Allow visiting the invoice module.'],
                    ['name' => 'page.admin.accounts.view', 'label' => 'Open account management', 'description' => 'Allow visiting the admin account and access management page.'],
                ],
            ],
            [
                'key' => 'actions',
                'label' => 'CRUD Actions',
                'description' => 'Controls create, update, delete, and permission-management actions.',
                'permissions' => [
                    ['name' => 'action.projects.create', 'label' => 'Create projects', 'description' => 'Allow creating new projects.'],
                    ['name' => 'action.projects.update', 'label' => 'Update projects', 'description' => 'Allow editing existing projects and uploading project documents.'],
                    ['name' => 'action.pipeline.create', 'label' => 'Create reports pipeline', 'description' => 'Allow creating marketing pipeline records.'],
                    ['name' => 'action.pipeline.update', 'label' => 'Update reports pipeline', 'description' => 'Allow editing marketing pipeline records.'],
                    ['name' => 'action.pipeline.delete', 'label' => 'Delete reports pipeline', 'description' => 'Allow deleting marketing pipeline records.'],
                    ['name' => 'action.invoices.create', 'label' => 'Create invoices', 'description' => 'Allow creating billing records.'],
                    ['name' => 'action.invoices.update', 'label' => 'Update invoices', 'description' => 'Allow editing billing records.'],
                    ['name' => 'action.invoices.delete', 'label' => 'Delete invoices', 'description' => 'Allow deleting billing records.'],
                    ['name' => 'action.project-costs.create', 'label' => 'Create cost realization', 'description' => 'Allow creating realized project cost records.'],
                    ['name' => 'action.project-costs.update', 'label' => 'Update cost realization', 'description' => 'Allow editing realized project cost records.'],
                    ['name' => 'action.project-costs.delete', 'label' => 'Delete cost realization', 'description' => 'Allow deleting realized project cost records.'],
                    ['name' => 'action.rabs.create', 'label' => 'Create RAB', 'description' => 'Allow creating RAB records.'],
                    ['name' => 'action.rabs.update', 'label' => 'Update RAB', 'description' => 'Allow editing RAB records and line items.'],
                    ['name' => 'action.rabs.delete', 'label' => 'Delete RAB', 'description' => 'Allow deleting RAB records.'],
                    ['name' => 'action.raps.create', 'label' => 'Create RAP', 'description' => 'Allow creating RAP records.'],
                    ['name' => 'action.raps.update', 'label' => 'Update RAP', 'description' => 'Allow editing RAP records and line items.'],
                    ['name' => 'action.raps.delete', 'label' => 'Delete RAP', 'description' => 'Allow deleting RAP records.'],
                    ['name' => 'action.progress-updates.create', 'label' => 'Create progress updates', 'description' => 'Allow creating project progress reports.'],
                    ['name' => 'action.progress-updates.update', 'label' => 'Update progress updates', 'description' => 'Allow editing project progress reports.'],
                    ['name' => 'action.progress-updates.delete', 'label' => 'Delete progress updates', 'description' => 'Allow deleting project progress reports.'],
                    ['name' => 'action.clients.create', 'label' => 'Create clients', 'description' => 'Allow creating client records.'],
                    ['name' => 'action.clients.update', 'label' => 'Update clients', 'description' => 'Allow editing client records.'],
                    ['name' => 'action.admin.accounts.create', 'label' => 'Create accounts', 'description' => 'Allow creating user accounts from the admin page.'],
                    ['name' => 'action.admin.accounts.update', 'label' => 'Update accounts', 'description' => 'Allow editing user accounts from the admin page.'],
                    ['name' => 'action.admin.accounts.delete', 'label' => 'Delete accounts', 'description' => 'Allow soft deleting user accounts from the admin page.'],
                    ['name' => 'action.admin.roles.manage', 'label' => 'Manage role permissions', 'description' => 'Allow changing which permissions belong to each role.'],
                ],
            ],
        ];
    }

    public static function permissionNames(): array
    {
        return collect(self::permissionGroups())
            ->flatMap(fn (array $group) => $group['permissions'])
            ->pluck('name')
            ->values()
            ->all();
    }

    public static function employeeRoleSuggestions(): array
    {
        return [
            ['value' => 'marketing', 'label' => 'Marketing'],
            ['value' => 'finance', 'label' => 'Finance'],
            ['value' => 'operational', 'label' => 'Operational'],
            ['value' => 'management', 'label' => 'Management'],
            ['value' => 'procurement', 'label' => 'Procurement'],
            ['value' => 'hr', 'label' => 'HR'],
        ];
    }

    public static function sidebarSections(): array
    {
        return [
            [
                'label' => null,
                'items' => [
                    ['title' => 'Dashboard', 'href' => route('dashboard'), 'icon' => 'Home', 'permission' => 'sidebar.dashboard.view'],
                ],
            ],
            [
                'label' => 'Marketing',
                'items' => [
                    ['title' => 'Penawaran / Pipeline', 'href' => route('pipeline'), 'icon' => 'Files', 'permission' => 'sidebar.marketing.reports.view'],
                ],
            ],
            [
                'label' => 'Projects',
                'items' => [
                    ['title' => 'Proyek', 'href' => route('projects'), 'icon' => 'Network', 'permission' => 'sidebar.marketing.projects.view'],
                ],
            ],
            [
                'label' => 'Budget',
                'items' => [
                    ['title' => 'RAB', 'href' => route('rabs'), 'icon' => 'FileText', 'permission' => 'sidebar.operational.rabs.view'],
                    ['title' => 'RAP', 'href' => route('raps'), 'icon' => 'FileText', 'permission' => 'sidebar.operational.raps.view'],
                ],
            ],
            [
                'label' => 'Progress',
                'items' => [
                    ['title' => 'Progress / BAMC', 'href' => route('progress-updates.index'), 'icon' => 'CopyCheck', 'permission' => 'sidebar.operational.progress.view'],
                ],
            ],
            [
                'label' => 'Finance',
                'items' => [
                    ['title' => 'Realisasi Biaya', 'href' => route('project-costs.index'), 'icon' => 'FileCheck', 'permission' => 'sidebar.finance.cost-realization.view'],
                    ['title' => 'Tagihan', 'href' => route('invoices.index'), 'icon' => 'BadgeDollarSign', 'permission' => 'sidebar.finance.billing.view'],
                ],
            ],
            [
                'label' => 'Clients',
                'items' => [
                    ['title' => 'Klien', 'href' => route('client'), 'icon' => 'Building2', 'permission' => 'sidebar.clients.view'],
                ],
            ],
            [
                'label' => 'Admin',
                'items' => [
                    ['title' => 'Users & Permissions', 'href' => route('admin.acc_mgmt'), 'icon' => 'Settings', 'permission' => 'sidebar.admin.users.view'],
                ],
            ],
        ];
    }

    public static function footerItems(): array
    {
        return [];
    }

    public static function sync(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::permissionNames() as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        foreach (self::defaultRoleNames() as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }

        foreach (self::defaultPermissionsByRole() as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');

            if ($roleName === 'admin') {
                $permissionIds = Permission::query()
                    ->whereIn('name', self::permissionNames())
                    ->where('guard_name', 'web')
                    ->pluck('id')
                    ->map(fn ($id): array => [
                        'permission_id' => $id,
                        'role_id' => $role->id,
                    ])
                    ->all();

                if ($permissionIds !== []) {
                    DB::table(config('permission.table_names.role_has_permissions'))
                        ->insertOrIgnore($permissionIds);
                }
            } elseif ($role->permissions()->count() === 0) {
                $role->syncPermissions(self::expandPermissions($permissions));
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public static function expandPermissions(array $permissions): array
    {
        $validPermissions = array_flip(self::permissionNames());
        $expanded = [];

        foreach ($permissions as $permission) {
            if (isset($validPermissions[$permission])) {
                $expanded[$permission] = true;
            }
        }

        do {
            $changed = false;

            foreach (array_keys($expanded) as $permission) {
                foreach (self::permissionDependencies()[$permission] ?? [] as $dependency) {
                    if (! isset($validPermissions[$dependency]) || isset($expanded[$dependency])) {
                        continue;
                    }

                    $expanded[$dependency] = true;
                    $changed = true;
                }
            }
        } while ($changed);

        return array_values(array_keys($expanded));
    }

    protected static function defaultRoleNames(): array
    {
        return array_values(array_unique([
            'admin',
            'employee',
            'client',
            ...collect(self::employeeRoleSuggestions())->pluck('value')->all(),
        ]));
    }

    protected static function permissionDependencies(): array
    {
        return [
            'page.dashboard.view' => ['sidebar.dashboard.view'],
            'page.billing-test.view' => ['sidebar.footer.testing.view', 'sidebar.finance.billing.view'],
            'page.projects.view' => ['sidebar.marketing.projects.view'],
            'page.rabs.view' => ['sidebar.operational.rabs.view'],
            'page.raps.view' => ['sidebar.operational.raps.view'],
            'page.pipeline.view' => ['sidebar.marketing.reports.view'],
            'page.clients.view' => ['sidebar.clients.view'],
            'page.fund-requests.view' => ['sidebar.operational.progress.view'],
            'page.invoices.view' => ['sidebar.finance.billing.view'],
            'page.admin.accounts.view' => ['sidebar.admin.users.view'],
            'action.projects.create' => ['page.projects.view', 'sidebar.marketing.projects.view'],
            'action.projects.update' => ['page.projects.view', 'sidebar.marketing.projects.view'],
            'action.pipeline.create' => ['page.pipeline.view', 'sidebar.marketing.reports.view'],
            'action.pipeline.update' => ['page.pipeline.view', 'sidebar.marketing.reports.view'],
            'action.pipeline.delete' => ['page.pipeline.view', 'sidebar.marketing.reports.view'],
            'action.invoices.create' => ['page.invoices.view', 'sidebar.finance.billing.view'],
            'action.invoices.update' => ['page.invoices.view', 'sidebar.finance.billing.view'],
            'action.invoices.delete' => ['page.invoices.view', 'sidebar.finance.billing.view'],
            'action.project-costs.create' => ['sidebar.finance.cost-realization.view'],
            'action.project-costs.update' => ['sidebar.finance.cost-realization.view'],
            'action.project-costs.delete' => ['sidebar.finance.cost-realization.view'],
            'action.rabs.create' => ['page.rabs.view', 'sidebar.operational.rabs.view'],
            'action.rabs.update' => ['page.rabs.view', 'sidebar.operational.rabs.view'],
            'action.rabs.delete' => ['page.rabs.view', 'sidebar.operational.rabs.view'],
            'action.raps.create' => ['page.raps.view', 'sidebar.operational.raps.view'],
            'action.raps.update' => ['page.raps.view', 'sidebar.operational.raps.view'],
            'action.raps.delete' => ['page.raps.view', 'sidebar.operational.raps.view'],
            'action.progress-updates.create' => ['sidebar.operational.progress.view'],
            'action.progress-updates.update' => ['sidebar.operational.progress.view'],
            'action.progress-updates.delete' => ['sidebar.operational.progress.view'],
            'action.clients.create' => ['page.clients.view', 'sidebar.clients.view'],
            'action.clients.update' => ['page.clients.view', 'sidebar.clients.view'],
            'action.admin.accounts.create' => ['page.admin.accounts.view', 'sidebar.admin.users.view'],
            'action.admin.accounts.update' => ['page.admin.accounts.view', 'sidebar.admin.users.view'],
            'action.admin.accounts.delete' => ['page.admin.accounts.view', 'sidebar.admin.users.view'],
            'action.admin.roles.manage' => ['page.admin.accounts.view', 'sidebar.admin.users.view'],
        ];
    }

    protected static function defaultPermissionsByRole(): array
    {
        return [
            'admin' => self::permissionNames(),
            'employee' => [
                'sidebar.dashboard.view',
                'sidebar.marketing.projects.view',
                'sidebar.operational.rabs.view',
                'sidebar.operational.raps.view',
                'sidebar.operational.progress.view',
                'page.dashboard.view',
                'page.projects.view',
                'page.rabs.view',
                'page.raps.view',
                'action.progress-updates.create',
                'action.progress-updates.update',
                'action.progress-updates.delete',
                'action.rabs.create',
                'action.rabs.update',
                'action.rabs.delete',
                'action.raps.create',
                'action.raps.update',
                'action.raps.delete',
            ],
            'client' => [
                'sidebar.dashboard.view',
                'page.dashboard.view',
            ],
            'marketing' => [
                'sidebar.dashboard.view',
                'sidebar.marketing.projects.view',
                'sidebar.marketing.reports.view',
                'sidebar.clients.view',
                'page.dashboard.view',
                'page.projects.view',
                'page.pipeline.view',
                'page.clients.view',
                'action.projects.create',
                'action.projects.update',
                'action.pipeline.create',
                'action.pipeline.update',
                'action.pipeline.delete',
                'action.clients.create',
                'action.clients.update',
            ],
            'finance' => [
                'sidebar.dashboard.view',
                'sidebar.finance.billing.view',
                'sidebar.finance.cost-realization.view',
                'page.dashboard.view',
                'page.invoices.view',
                'action.invoices.create',
                'action.invoices.update',
                'action.invoices.delete',
                'action.project-costs.create',
                'action.project-costs.update',
                'action.project-costs.delete',
            ],
            'operational' => [
                'sidebar.dashboard.view',
                'sidebar.operational.rabs.view',
                'sidebar.operational.raps.view',
                'sidebar.operational.progress.view',
                'page.dashboard.view',
                'page.rabs.view',
                'page.raps.view',
                'page.fund-requests.view',
                'action.progress-updates.create',
                'action.progress-updates.update',
                'action.progress-updates.delete',
            ],
            'management' => [
                'sidebar.dashboard.view',
                'sidebar.marketing.projects.view',
                'sidebar.clients.view',
                'page.dashboard.view',
                'page.projects.view',
                'page.clients.view',
            ],
            'procurement' => [
                'sidebar.dashboard.view',
                'sidebar.marketing.projects.view',
                'page.dashboard.view',
                'page.projects.view',
            ],
            'hr' => [
                'sidebar.dashboard.view',
                'page.dashboard.view',
            ],
        ];
    }
}
