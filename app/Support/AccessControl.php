<?php

namespace App\Support;

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
                'label' => 'Visibilitas Sidebar',
                'description' => 'Mengatur section dan shortcut sidebar yang terlihat untuk sebuah role.',
                'permissions' => [
                    ['name' => 'sidebar.dashboard.view', 'label' => 'Menu Dashboard', 'description' => 'Tampilkan link dashboard di sidebar.'],
                    ['name' => 'sidebar.finance.billing.view', 'label' => 'Menu Tagihan', 'description' => 'Tampilkan shortcut tagihan di section finance.'],
                    ['name' => 'sidebar.finance.cost-realization.view', 'label' => 'Menu Realisasi Biaya', 'description' => 'Tampilkan shortcut realisasi biaya di section finance.'],
                    ['name' => 'sidebar.marketing.projects.view', 'label' => 'Menu Proyek', 'description' => 'Tampilkan halaman proyek di section marketing.'],
                    ['name' => 'sidebar.marketing.reports.view', 'label' => 'Menu Laporan', 'description' => 'Tampilkan shortcut laporan atau pipeline di section marketing.'],
                    ['name' => 'sidebar.operational.rabs.view', 'label' => 'Menu RAB', 'description' => 'Tampilkan halaman RAB di section operational.'],
                    ['name' => 'sidebar.operational.raps.view', 'label' => 'Menu RAP', 'description' => 'Tampilkan halaman RAP di section operational.'],
                    ['name' => 'sidebar.operational.progress.view', 'label' => 'Menu Progress Update', 'description' => 'Tampilkan shortcut progress update di section operational.'],
                    ['name' => 'sidebar.clients.view', 'label' => 'Menu Klien', 'description' => 'Tampilkan shortcut klien di sidebar.'],
                    ['name' => 'sidebar.admin.users.view', 'label' => 'Menu User Admin', 'description' => 'Tampilkan shortcut manajemen user dan permission di sidebar.'],
                    ['name' => 'sidebar.footer.testing.view', 'label' => 'Shortcut Testing', 'description' => 'Tampilkan shortcut halaman testing di footer.'],
                    ['name' => 'sidebar.footer.accounts.view', 'label' => 'Shortcut Manajemen Akun', 'description' => 'Tampilkan shortcut manajemen akun di footer.'],
                ],
            ],
            [
                'key' => 'pages',
                'label' => 'Akses Halaman',
                'description' => 'Mengatur siapa yang bisa membuka setiap halaman atau modul.',
                'permissions' => [
                    ['name' => 'page.dashboard.view', 'label' => 'Buka Dashboard', 'description' => 'Izinkan membuka dashboard utama.'],
                    ['name' => 'page.billing-test.view', 'label' => 'Buka Billing Test', 'description' => 'Izinkan membuka halaman billing test.'],
                    ['name' => 'page.projects.view', 'label' => 'Buka Proyek', 'description' => 'Izinkan membuka daftar dan detail proyek.'],
                    ['name' => 'page.rabs.view', 'label' => 'Buka RAB', 'description' => 'Izinkan membuka daftar dan detail RAB.'],
                    ['name' => 'page.raps.view', 'label' => 'Buka RAP', 'description' => 'Izinkan membuka daftar dan detail RAP.'],
                    ['name' => 'page.pipeline.view', 'label' => 'Buka Pipeline Laporan', 'description' => 'Izinkan membuka halaman laporan marketing atau pipeline.'],
                    ['name' => 'page.clients.view', 'label' => 'Buka Klien', 'description' => 'Izinkan membuka halaman klien.'],
                    ['name' => 'page.fund-requests.view', 'label' => 'Buka Pengajuan Dana', 'description' => 'Izinkan membuka modul pengajuan dana.'],
                    ['name' => 'page.invoices.view', 'label' => 'Buka Invoice', 'description' => 'Izinkan membuka modul invoice.'],
                    ['name' => 'page.admin.accounts.view', 'label' => 'Buka Manajemen Akun', 'description' => 'Izinkan membuka halaman admin akun dan manajemen akses.'],
                ],
            ],
            [
                'key' => 'actions',
                'label' => 'Aksi CRUD',
                'description' => 'Mengatur aksi create, update, delete, dan manajemen permission.',
                'permissions' => [
                    ['name' => 'action.projects.create', 'label' => 'Tambah Proyek', 'description' => 'Izinkan membuat proyek baru.'],
                    ['name' => 'action.projects.update', 'label' => 'Update Proyek', 'description' => 'Izinkan edit proyek dan upload dokumen proyek.'],
                    ['name' => 'action.pipeline.create', 'label' => 'Tambah Pipeline Laporan', 'description' => 'Izinkan membuat data pipeline marketing.'],
                    ['name' => 'action.pipeline.update', 'label' => 'Update Pipeline Laporan', 'description' => 'Izinkan edit data pipeline marketing.'],
                    ['name' => 'action.pipeline.delete', 'label' => 'Hapus Pipeline Laporan', 'description' => 'Izinkan menghapus data pipeline marketing.'],
                    ['name' => 'action.invoices.create', 'label' => 'Tambah Invoice', 'description' => 'Izinkan membuat data tagihan.'],
                    ['name' => 'action.invoices.update', 'label' => 'Update Invoice', 'description' => 'Izinkan edit data tagihan.'],
                    ['name' => 'action.invoices.delete', 'label' => 'Hapus Invoice', 'description' => 'Izinkan menghapus data tagihan.'],
                    ['name' => 'action.project-costs.create', 'label' => 'Tambah Realisasi Biaya', 'description' => 'Izinkan membuat data realisasi biaya proyek.'],
                    ['name' => 'action.project-costs.update', 'label' => 'Update Realisasi Biaya', 'description' => 'Izinkan edit data realisasi biaya proyek.'],
                    ['name' => 'action.project-costs.delete', 'label' => 'Hapus Realisasi Biaya', 'description' => 'Izinkan menghapus data realisasi biaya proyek.'],
                    ['name' => 'action.rabs.create', 'label' => 'Tambah RAB', 'description' => 'Izinkan membuat data RAB.'],
                    ['name' => 'action.rabs.update', 'label' => 'Update RAB', 'description' => 'Izinkan edit data RAB dan itemnya.'],
                    ['name' => 'action.rabs.delete', 'label' => 'Hapus RAB', 'description' => 'Izinkan menghapus data RAB.'],
                    ['name' => 'action.raps.create', 'label' => 'Tambah RAP', 'description' => 'Izinkan membuat data RAP.'],
                    ['name' => 'action.raps.update', 'label' => 'Update RAP', 'description' => 'Izinkan edit data RAP dan itemnya.'],
                    ['name' => 'action.raps.delete', 'label' => 'Hapus RAP', 'description' => 'Izinkan menghapus data RAP.'],
                    ['name' => 'action.progress-updates.create', 'label' => 'Tambah Progress Update', 'description' => 'Izinkan membuat laporan progress proyek.'],
                    ['name' => 'action.progress-updates.update', 'label' => 'Update Progress Update', 'description' => 'Izinkan edit laporan progress proyek.'],
                    ['name' => 'action.progress-updates.delete', 'label' => 'Hapus Progress Update', 'description' => 'Izinkan menghapus laporan progress proyek.'],
                    ['name' => 'action.clients.create', 'label' => 'Tambah Klien', 'description' => 'Izinkan membuat data klien.'],
                    ['name' => 'action.clients.update', 'label' => 'Update Klien', 'description' => 'Izinkan edit data klien.'],
                    ['name' => 'action.admin.accounts.create', 'label' => 'Tambah Akun', 'description' => 'Izinkan membuat akun user dari halaman admin.'],
                    ['name' => 'action.admin.accounts.update', 'label' => 'Update Akun', 'description' => 'Izinkan edit akun user dari halaman admin.'],
                    ['name' => 'action.admin.accounts.delete', 'label' => 'Hapus Akun', 'description' => 'Izinkan soft delete akun user dari halaman admin.'],
                    ['name' => 'action.admin.roles.manage', 'label' => 'Kelola Permission Role', 'description' => 'Izinkan mengubah permission yang dimiliki setiap role.'],
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
                'label' => 'Proyek',
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
                'label' => 'Klien',
                'items' => [
                    ['title' => 'Klien', 'href' => route('client'), 'icon' => 'Building2', 'permission' => 'sidebar.clients.view'],
                ],
            ],
            [
                'label' => 'Admin',
                'items' => [
                    ['title' => 'User & Permission', 'href' => route('admin.acc_mgmt'), 'icon' => 'Settings', 'permission' => 'sidebar.admin.users.view'],
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
                $role->syncPermissions(self::permissionNames());
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
