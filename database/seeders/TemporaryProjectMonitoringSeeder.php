<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TemporaryProjectMonitoringSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Buat Role (Tanpa role 'client')
        foreach ($this->sidebarRoles() as $role) {
            Role::findOrCreate($role, 'web');
        }

        DB::transaction(function () {
            $now = now();

            // 2. Buat User Internal JTE (Tanpa client_id)
            $jteUsers = [
                'manager' => $this->upsertUser('manager@jte.test', [
                    'name' => 'JTE Project Manager',
                    'password' => Hash::make('password'),
                    'user_type' => 'employee',
                    'employee_role' => 'operational',
                    'email_verified_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
                'finance' => $this->upsertUser('finance@jte.test', [
                    'name' => 'JTE Finance',
                    'password' => Hash::make('password'),
                    'user_type' => 'employee',
                    'employee_role' => 'finance',
                    'email_verified_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
                'field' => $this->upsertUser('field@jte.test', [
                    'name' => 'JTE Field Supervisor',
                    'password' => Hash::make('password'),
                    'user_type' => 'employee',
                    'employee_role' => 'operational',
                    'email_verified_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
                'director' => $this->upsertUser('director@jte.test', [
                    'name' => 'JTE Director',
                    'password' => Hash::make('password'),
                    'user_type' => 'employee',
                    'employee_role' => 'management',
                    'email_verified_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
            ];

            // 3. Data Perusahaan Client & Proyek
            $clients = [
                [
                    'name' => 'PT Nusantara Konstruksi',
                    'contact' => 'client@nusantara.test',
                    'projects' => [
                        [
                            'name' => 'Pembangunan Gudang Distribusi',
                            'contract_number' => 'JTE/PM/2026/001',
                            'contract_value' => 2750000000.00,
                            'start_date' => '2026-04-01',
                            'end_date' => '2026-11-30',
                            'location' => 'Bekasi, Jawa Barat',
                            'status' => 'ongoing',
                            'team' => [
                                ['user' => 'manager', 'role' => 'manager'],
                                ['user' => 'finance', 'role' => 'finance'],
                                ['user' => 'field', 'role' => 'field'],
                                ['user' => 'director', 'role' => 'director'],
                            ],
                            'tender' => [
                                'title' => 'Tender Gudang Distribusi Bekasi',
                                'value' => 2800000000.00,
                                'status' => 'won',
                            ],
                            'rab' => [
                                'total_budget' => 2400000000.00,
                                'items' => [
                                    ['description' => 'Pekerjaan Struktur Beton', 'quantity' => 1, 'unit_price' => 1200000000.00, 'total_price' => 1200000000.00],
                                    ['description' => 'Pekerjaan Baja Ringan', 'quantity' => 1, 'unit_price' => 430000000.00, 'total_price' => 430000000.00],
                                ],
                            ],
                            'rap' => [
                                'total_budget' => 2250000000.00,
                                'items' => [
                                    ['description' => 'Ready Mix Concrete', 'quantity' => 250, 'unit_price' => 950000.00, 'total_price' => 237500000.00, 'spec_brand' => 'Merah Beton', 'spec_size' => 'm3', 'spec_strength' => 'K-350'],
                                ],
                            ],
                            'progress_reports' => [
                                ['progress_percent' => 18, 'report_date' => '2026-04-07', 'description' => 'Mobilisasi tim dan pekerjaan pondasi.', 'approved_by_client' => true, 'approved_by_internal' => true],
                            ],
                            'project_costs' => [
                                ['category' => 'Mobilization', 'amount' => 85000000.00, 'date' => '2026-04-05'],
                            ],
                            'invoices' => [
                                ['amount' => 350000000.00, 'invoice_date' => '2026-04-08', 'status' => 'pending', 'payments' => [['amount' => 150000000.00, 'payment_date' => '2026-04-09']]],
                            ],
                            'fund_requests' => [
                                ['requested_by' => 'manager', 'amount' => 125000000.00, 'status' => 'approved_manager'],
                            ],
                        ],
                    ],
                ],
            ];

            foreach ($clients as $clientSeed) {
                // Upsert data perusahaan ke tabel clients
                $clientId = $this->upsertClient($clientSeed['name'], [
                    'contact' => $clientSeed['contact'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                foreach ($clientSeed['projects'] as $projectSeed) {
                    // Buat Proyek yang terhubung ke clientId
                    $projectId = $this->upsertProject($projectSeed['contract_number'], [
                        'client_id' => $clientId,
                        'name' => $projectSeed['name'],
                        'contract_value' => $projectSeed['contract_value'],
                        'start_date' => $projectSeed['start_date'],
                        'end_date' => $projectSeed['end_date'],
                        'location' => $projectSeed['location'],
                        'status' => $projectSeed['status'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    // Seed semua relasi proyek (Team hanya berisi JTE Users)
                    $this->seedProjectUsers($projectId, $projectSeed['team'], $jteUsers);
                    $this->seedTender($projectSeed['tender'], $now);
                    $this->seedRab($projectId, $projectSeed['rab'], $now);
                    $this->seedRap($projectId, $projectSeed['rap'], $now);
                    $this->seedProgressReports($projectId, $projectSeed['progress_reports'], $now);
                    $this->seedProjectCosts($projectId, $projectSeed['project_costs']);
                    $this->seedInvoices($projectId, $projectSeed['invoices'], $now);
                    $this->seedFundRequests($projectId, $projectSeed['fund_requests'], $jteUsers, $now);
                }
            }
        });

        $this->syncSeededUserRoles();
    }

    private function upsertClient(string $name, array $values): int
    {
        DB::table('clients')->updateOrInsert(['name' => $name], $values);
        return (int) DB::table('clients')->where('name', $name)->value('id');
    }

    private function upsertUser(string $email, array $values): int
    {
        DB::table('users')->updateOrInsert(['email' => $email], $values);
        return (int) DB::table('users')->where('email', $email)->value('id');
    }

    private function upsertProject(string $contractNumber, array $values): int
    {
        DB::table('projects')->updateOrInsert(['contract_number' => $contractNumber], $values);
        return (int) DB::table('projects')->where('contract_number', $contractNumber)->value('id');
    }

    private function seedProjectUsers(int $projectId, array $teamSeeds, array $users): void
    {
        foreach ($teamSeeds as $teamSeed) {
            DB::table('project_users')->updateOrInsert(
                [
                    'project_id' => $projectId,
                    'user_id' => $users[$teamSeed['user']],
                ],
                [
                    'role' => $teamSeed['role'],
                    'deleted_at' => null,
                ]
            );
        }
    }

    private function seedTender(array $tenderSeed, mixed $now): void
    {
        DB::table('tenders')->updateOrInsert(
            ['title' => $tenderSeed['title']],
            [
                'value' => $tenderSeed['value'],
                'status' => $tenderSeed['status'],
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    private function seedRab(int $projectId, array $rabSeed, mixed $now): void
    {
        DB::table('rabs')->updateOrInsert(['project_id' => $projectId], [
            'total_budget' => $rabSeed['total_budget'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $rabId = DB::table('rabs')->where('project_id', $projectId)->value('id');

        foreach ($rabSeed['items'] as $item) {
            DB::table('rab_items')->updateOrInsert(
                ['rab_id' => $rabId, 'description' => $item['description']],
                ['quantity' => $item['quantity'], 'unit_price' => $item['unit_price'], 'total_price' => $item['total_price']]
            );
        }
    }

    private function seedRap(int $projectId, array $rapSeed, mixed $now): void
    {
        DB::table('raps')->updateOrInsert(['project_id' => $projectId], [
            'total_budget' => $rapSeed['total_budget'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $rapId = DB::table('raps')->where('project_id', $projectId)->value('id');

        foreach ($rapSeed['items'] as $item) {
            DB::table('rap_items')->updateOrInsert(
                ['rap_id' => $rapId, 'description' => $item['description']],
                [
                    'quantity' => $item['quantity'], 'unit_price' => $item['unit_price'], 
                    'total_price' => $item['total_price'], 'spec_brand' => $item['spec_brand'],
                    'spec_size' => $item['spec_size'], 'spec_strength' => $item['spec_strength']
                ]
            );
        }
    }

    private function seedProgressReports(int $projectId, array $reportSeeds, mixed $now): void
    {
        foreach ($reportSeeds as $reportSeed) {
            DB::table('progress_reports')->updateOrInsert(
                ['project_id' => $projectId, 'report_date' => $reportSeed['report_date']],
                ['progress_percent' => $reportSeed['progress_percent'], 'description' => $reportSeed['description'], 'created_at' => $now]
            );

            $progressReportId = DB::table('progress_reports')->where('project_id', $projectId)->where('report_date', $reportSeed['report_date'])->value('id');

            DB::table('progress_approvals')->updateOrInsert(
                ['progress_report_id' => $progressReportId],
                ['approved_by_client' => $reportSeed['approved_by_client'], 'approved_by_internal' => $reportSeed['approved_by_internal']]
            );
        }
    }

    private function seedProjectCosts(int $projectId, array $costSeeds): void
    {
        foreach ($costSeeds as $costSeed) {
            DB::table('project_costs')->updateOrInsert(
                ['project_id' => $projectId, 'category' => $costSeed['category'], 'date' => $costSeed['date']],
                ['amount' => $costSeed['amount']]
            );
        }
    }

    private function seedInvoices(int $projectId, array $invoiceSeeds, mixed $now): void
    {
        foreach ($invoiceSeeds as $invoiceSeed) {
            DB::table('invoices')->updateOrInsert(
                ['project_id' => $projectId, 'invoice_date' => $invoiceSeed['invoice_date']],
                ['amount' => $invoiceSeed['amount'], 'status' => $invoiceSeed['status'], 'created_at' => $now]
            );

            $invoiceId = DB::table('invoices')->where('project_id', $projectId)->where('invoice_date', $invoiceSeed['invoice_date'])->value('id');

            foreach ($invoiceSeed['payments'] as $paymentSeed) {
                DB::table('payments')->updateOrInsert(
                    ['invoice_id' => $invoiceId, 'payment_date' => $paymentSeed['payment_date']],
                    ['amount' => $paymentSeed['amount']]
                );
            }
        }
    }

    private function seedFundRequests(int $projectId, array $fundRequestSeeds, array $users, mixed $now): void
    {
        foreach ($fundRequestSeeds as $fundRequestSeed) {
            DB::table('fund_requests')->updateOrInsert(
                [
                    'project_id' => $projectId,
                    'requested_by' => $users[$fundRequestSeed['requested_by']],
                    'amount' => $fundRequestSeed['amount'],
                ],
                ['status' => $fundRequestSeed['status'], 'created_at' => $now]
            );
        }
    }

    private function sidebarRoles(): array
    {
        // Role 'client' dihapus dari daftar
        return ['admin', 'employee', 'finance', 'marketing', 'operational', 'procurement', 'hr', 'management'];
    }

    private function syncSeededUserRoles(): void
    {
        User::query()->get()->each(function (User $user): void {
            $roles = $user->sidebarRoleNames();
            if (!empty($roles)) {
                $user->syncRoles($roles);
            }
        });
    }
}