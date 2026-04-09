<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AccessControl
{
    /*
     * Single source of truth for sidebar navigation, permission keys,
     * and default role assignments. Update this file when adding or
     * renaming a sidebar item, page, or CRUD capability.
     */
    /**
     * @return list<array{
     *     key: string,
     *     label: string,
     *     description: string,
     *     permissions: list<array{name: string, label: string, description: string}>
     * }>
     */
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
                    ['name' => 'sidebar.finance.profit-loss.view', 'label' => 'Profit and loss menu', 'description' => 'Show the profit and loss shortcut in the finance section.'],
                    ['name' => 'sidebar.marketing.projects.view', 'label' => 'Projects menu', 'description' => 'Show the projects page in the marketing section.'],
                    ['name' => 'sidebar.marketing.clients.view', 'label' => 'Clients menu', 'description' => 'Show the client page in the marketing section.'],
                    ['name' => 'sidebar.marketing.reports.view', 'label' => 'Reports menu', 'description' => 'Show the reports or pipeline shortcut in the marketing section.'],
                    ['name' => 'sidebar.operational.rabs.view', 'label' => 'RAB menu', 'description' => 'Show the RAB page in the operational section.'],
                    ['name' => 'sidebar.operational.raps.view', 'label' => 'RAP menu', 'description' => 'Show the RAP page in the operational section.'],
                    ['name' => 'sidebar.operational.progress.view', 'label' => 'Progress update menu', 'description' => 'Show the progress update shortcut in the operational section.'],
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
                    ['name' => 'page.clients.view', 'label' => 'Open clients', 'description' => 'Allow visiting the client list and details.'],
                    ['name' => 'page.rabs.view', 'label' => 'Open RAB', 'description' => 'Allow visiting the RAB list and detail page.'],
                    ['name' => 'page.raps.view', 'label' => 'Open RAP', 'description' => 'Allow visiting the RAP list and detail page.'],
                    ['name' => 'page.pipeline.view', 'label' => 'Open reports pipeline', 'description' => 'Allow visiting the marketing reports or pipeline page.'],
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
                    ['name' => 'action.clients.create', 'label' => 'Create clients', 'description' => 'Allow creating new clients.'],
                    ['name' => 'action.clients.update', 'label' => 'Update clients', 'description' => 'Allow editing existing clients.'],
                    ['name' => 'action.admin.accounts.create', 'label' => 'Create accounts', 'description' => 'Allow creating user accounts from the admin page.'],
                    ['name' => 'action.admin.accounts.update', 'label' => 'Update accounts', 'description' => 'Allow editing user accounts from the admin page.'],
                    ['name' => 'action.admin.accounts.delete', 'label' => 'Delete accounts', 'description' => 'Allow soft deleting user accounts from the admin page.'],
                    ['name' => 'action.admin.roles.manage', 'label' => 'Manage role permissions', 'description' => 'Allow changing which permissions belong to each role.'],
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function permissionNames(): array
    {
        return collect(self::permissionGroups())
            ->flatMap(fn (array $group) => $group['permissions'])
            ->pluck('name')
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function employeeRoleSuggestions(): array
    {
        return [
            ['value' => 'marketing', 'label' => 'Marketing'],
            ['value' => 'finance', 'label' => 'Finance'],
            ['value' => 'operational', 'label' => 'Operational'],
            ['value' => 'procurement', 'label' => 'Procurement'],
            ['value' => 'hr', 'label' => 'HR'],
        ];
    }

    /**
     * @return list<array{
     *     label: null|string,
     *     items: list<array{title: string, href: string, icon: string, permission: string}>
     * }>
     */
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
                'label' => 'Finance',
                'items' => [
                    ['title' => 'Billing', 'href' => route('billing.test'), 'icon' => 'BadgeDollarSign', 'permission' => 'sidebar.finance.billing.view'],
                    ['title' => 'Cost Realization', 'href' => '#', 'icon' => 'FileCheck', 'permission' => 'sidebar.finance.cost-realization.view'],
                    ['title' => 'Profit and Loss', 'href' => '#', 'icon' => 'ChartLine', 'permission' => 'sidebar.finance.profit-loss.view'],
                ],
            ],
            [
                'label' => 'Marketing',
                'items' => [
                    ['title' => 'Projects', 'href' => route('projects'), 'icon' => 'Network', 'permission' => 'sidebar.marketing.projects.view'],
                    ['title' => 'Client', 'href' => route('client'), 'icon' => 'Users', 'permission' => 'sidebar.marketing.clients.view'],
                    ['title' => 'Reports', 'href' => route('pipeline'), 'icon' => 'Files', 'permission' => 'sidebar.marketing.reports.view'],
                ],
            ],
            [
                'label' => 'Operational',
                'items' => [
                    ['title' => 'RAB', 'href' => route('rabs'), 'icon' => 'FileText', 'permission' => 'sidebar.operational.rabs.view'],
                    ['title' => 'RAP', 'href' => route('raps'), 'icon' => 'FileText', 'permission' => 'sidebar.operational.raps.view'],
                    ['title' => 'Progress Update', 'href' => '#', 'icon' => 'CopyCheck', 'permission' => 'sidebar.operational.progress.view'],
                ],
            ],
        ];
    }

    /**
     * @return list<array{title: string, href: string, icon: string, permission: string}>
     */
    public static function footerItems(): array
    {
        return [
            ['title' => 'Testing', 'href' => route('billing.test'), 'icon' => 'TestTube', 'permission' => 'sidebar.footer.testing.view'],
            ['title' => 'Account Management', 'href' => route('admin.acc_mgmt'), 'icon' => 'Settings', 'permission' => 'sidebar.footer.accounts.view'],
        ];
    }

    public static function sync(): void
    {
        foreach (self::permissionNames() as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        foreach (self::defaultRoleNames() as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }

        foreach (self::defaultPermissionsByRole() as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');

            if ($role->permissions()->count() === 0) {
                $role->syncPermissions($permissions);
            }
        }
    }

    /**
     * @return list<string>
     */
    protected static function defaultRoleNames(): array
    {
        return array_values(array_unique([
            'admin',
            'employee',
            'client',
            ...collect(self::employeeRoleSuggestions())->pluck('value')->all(),
        ]));
    }

    /**
     * @return array<string, list<string>>
     */
    protected static function defaultPermissionsByRole(): array
    {
        return [
            'admin' => self::permissionNames(),
            'employee' => [
                'sidebar.dashboard.view',
                'sidebar.marketing.projects.view',
                'sidebar.marketing.clients.view',
                'sidebar.operational.rabs.view',
                'sidebar.operational.raps.view',
                'page.dashboard.view',
                'page.projects.view',
                'page.clients.view',
                'page.rabs.view',
                'page.raps.view',
            ],
            'client' => [
                'sidebar.dashboard.view',
                'page.dashboard.view',
            ],
            'marketing' => [
                'sidebar.dashboard.view',
                'sidebar.marketing.projects.view',
                'sidebar.marketing.clients.view',
                'sidebar.marketing.reports.view',
                'page.dashboard.view',
                'page.projects.view',
                'page.clients.view',
                'page.pipeline.view',
                'action.projects.create',
                'action.projects.update',
                'action.clients.create',
                'action.clients.update',
            ],
            'finance' => [
                'sidebar.dashboard.view',
                'sidebar.finance.billing.view',
                'sidebar.finance.cost-realization.view',
                'sidebar.finance.profit-loss.view',
                'sidebar.footer.testing.view',
                'page.dashboard.view',
                'page.billing-test.view',
                'page.invoices.view',
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
