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
        foreach ($this->sidebarRoles() as $role) {
            Role::findOrCreate($role, 'web');
        }

        DB::transaction(function () {
            $now = now();

            $jteUsers = [
                'manager' => $this->upsertUser('manager@jte.test', [
                    'client_id' => null,
                    'name' => 'JTE Project Manager',
                    'password' => Hash::make('password'),
                    'user_type' => 'employee',
                    'employee_role' => 'operational',
                    'email_verified_at' => $now,
                    'remember_token' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
                'finance' => $this->upsertUser('finance@jte.test', [
                    'client_id' => null,
                    'name' => 'JTE Finance',
                    'password' => Hash::make('password'),
                    'user_type' => 'employee',
                    'employee_role' => 'finance',
                    'email_verified_at' => $now,
                    'remember_token' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
                'field' => $this->upsertUser('field@jte.test', [
                    'client_id' => null,
                    'name' => 'JTE Field Supervisor',
                    'password' => Hash::make('password'),
                    'user_type' => 'employee',
                    'employee_role' => 'operational',
                    'email_verified_at' => $now,
                    'remember_token' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
                'director' => $this->upsertUser('director@jte.test', [
                    'client_id' => null,
                    'name' => 'JTE Director',
                    'password' => Hash::make('password'),
                    'user_type' => 'employee',
                    'employee_role' => 'management',
                    'email_verified_at' => $now,
                    'remember_token' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
            ];

            $clients = [
                [
                    'name' => 'PT Nusantara Konstruksi',
                    'contact' => 'client@nusantara.test',
                    'client_user' => [
                        'name' => 'Nusantara Client PIC',
                        'email' => 'pic@nusantara.test',
                    ],
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
                                ['user' => 'client', 'role' => 'client'],
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
                                    ['description' => 'Pekerjaan Arsitektur', 'quantity' => 1, 'unit_price' => 520000000.00, 'total_price' => 520000000.00],
                                ],
                            ],
                            'rap' => [
                                'total_budget' => 2250000000.00,
                                'items' => [
                                    ['description' => 'Ready Mix Concrete', 'quantity' => 250, 'unit_price' => 950000.00, 'total_price' => 237500000.00, 'spec_brand' => 'Merah Beton', 'spec_size' => 'm3', 'spec_strength' => 'K-350'],
                                    ['description' => 'Besi Ulir', 'quantity' => 18000, 'unit_price' => 14500.00, 'total_price' => 261000000.00, 'spec_brand' => 'Krakatau', 'spec_size' => 'D16', 'spec_strength' => 'BJTS 420'],
                                ],
                            ],
                            'progress_reports' => [
                                ['progress_percent' => 18, 'report_date' => '2026-04-07', 'description' => 'Mobilisasi tim dan pekerjaan pondasi awal telah berjalan.', 'approved_by_client' => true, 'approved_by_internal' => true],
                                ['progress_percent' => 34, 'report_date' => '2026-05-01', 'description' => 'Pengecoran kolom dan balok lantai dasar telah selesai.', 'approved_by_client' => true, 'approved_by_internal' => true],
                            ],
                            'project_costs' => [
                                ['category' => 'Mobilization', 'amount' => 85000000.00, 'date' => '2026-04-05'],
                                ['category' => 'Material', 'amount' => 340000000.00, 'date' => '2026-04-20'],
                            ],
                            'invoices' => [
                                ['amount' => 350000000.00, 'invoice_date' => '2026-04-08', 'status' => 'pending', 'payments' => [['amount' => 150000000.00, 'payment_date' => '2026-04-09']]],
                                ['amount' => 500000000.00, 'invoice_date' => '2026-05-10', 'status' => 'paid', 'payments' => [['amount' => 500000000.00, 'payment_date' => '2026-05-18']]],
                            ],
                            'fund_requests' => [
                                ['requested_by' => 'manager', 'amount' => 125000000.00, 'status' => 'approved_manager'],
                                ['requested_by' => 'field', 'amount' => 45000000.00, 'status' => 'pending'],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'CV Sejahtera Abadi',
                    'contact' => 'hello@sejahtera.test',
                    'client_user' => [
                        'name' => 'Sejahtera Representative',
                        'email' => 'pic@sejahtera.test',
                    ],
                    'projects' => [
                        [
                            'name' => 'Renovasi Gedung Perkantoran',
                            'contract_number' => 'JTE/PM/2026/002',
                            'contract_value' => 1850000000.00,
                            'start_date' => '2026-02-15',
                            'end_date' => '2026-08-30',
                            'location' => 'Jakarta Selatan',
                            'status' => 'planning',
                            'team' => [
                                ['user' => 'manager', 'role' => 'manager'],
                                ['user' => 'finance', 'role' => 'finance'],
                                ['user' => 'client', 'role' => 'client'],
                            ],
                            'tender' => [
                                'title' => 'Tender Renovasi Gedung Perkantoran',
                                'value' => 1900000000.00,
                                'status' => 'submitted',
                            ],
                            'rab' => [
                                'total_budget' => 1625000000.00,
                                'items' => [
                                    ['description' => 'Pekerjaan Interior', 'quantity' => 1, 'unit_price' => 720000000.00, 'total_price' => 720000000.00],
                                    ['description' => 'Pekerjaan MEP', 'quantity' => 1, 'unit_price' => 505000000.00, 'total_price' => 505000000.00],
                                ],
                            ],
                            'rap' => [
                                'total_budget' => 1540000000.00,
                                'items' => [
                                    ['description' => 'Gypsum Board', 'quantity' => 1400, 'unit_price' => 128000.00, 'total_price' => 179200000.00, 'spec_brand' => 'Jayaboard', 'spec_size' => '120x240', 'spec_strength' => 'Standard'],
                                ],
                            ],
                            'progress_reports' => [
                                ['progress_percent' => 5, 'report_date' => '2026-03-02', 'description' => 'Pengukuran awal dan finalisasi layout desain dilakukan.', 'approved_by_client' => true, 'approved_by_internal' => true],
                            ],
                            'project_costs' => [
                                ['category' => 'Survey', 'amount' => 12500000.00, 'date' => '2026-02-18'],
                            ],
                            'invoices' => [
                                ['amount' => 200000000.00, 'invoice_date' => '2026-03-05', 'status' => 'overdue', 'payments' => []],
                            ],
                            'fund_requests' => [
                                ['requested_by' => 'manager', 'amount' => 80000000.00, 'status' => 'approved_finance'],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'PT Cipta Sarana Teknik',
                    'contact' => 'admin@ciptasarana.test',
                    'client_user' => [
                        'name' => 'Cipta Sarana Owner',
                        'email' => 'pic@ciptasarana.test',
                    ],
                    'projects' => [
                        [
                            'name' => 'Pembangunan Workshop Fabrikasi',
                            'contract_number' => 'JTE/PM/2026/003',
                            'contract_value' => 3200000000.00,
                            'start_date' => '2026-01-10',
                            'end_date' => '2026-10-15',
                            'location' => 'Karawang, Jawa Barat',
                            'status' => 'completed',
                            'team' => [
                                ['user' => 'manager', 'role' => 'manager'],
                                ['user' => 'finance', 'role' => 'finance'],
                                ['user' => 'field', 'role' => 'field'],
                                ['user' => 'client', 'role' => 'client'],
                            ],
                            'tender' => [
                                'title' => 'Tender Workshop Fabrikasi',
                                'value' => 3300000000.00,
                                'status' => 'won',
                            ],
                            'rab' => [
                                'total_budget' => 2860000000.00,
                                'items' => [
                                    ['description' => 'Struktur Baja Utama', 'quantity' => 1, 'unit_price' => 1480000000.00, 'total_price' => 1480000000.00],
                                    ['description' => 'Pekerjaan Finishing', 'quantity' => 1, 'unit_price' => 380000000.00, 'total_price' => 380000000.00],
                                ],
                            ],
                            'rap' => [
                                'total_budget' => 2710000000.00,
                                'items' => [
                                    ['description' => 'H Beam', 'quantity' => 120, 'unit_price' => 18500000.00, 'total_price' => 2220000000.00, 'spec_brand' => 'Gunung Steel', 'spec_size' => '400x200', 'spec_strength' => 'Grade A'],
                                ],
                            ],
                            'progress_reports' => [
                                ['progress_percent' => 65, 'report_date' => '2026-05-15', 'description' => 'Struktur utama dan atap workshop telah berdiri.', 'approved_by_client' => true, 'approved_by_internal' => true],
                                ['progress_percent' => 100, 'report_date' => '2026-09-30', 'description' => 'Pekerjaan selesai dan siap serah terima.', 'approved_by_client' => true, 'approved_by_internal' => true],
                            ],
                            'project_costs' => [
                                ['category' => 'Steel Material', 'amount' => 1680000000.00, 'date' => '2026-03-10'],
                                ['category' => 'Subcontractor', 'amount' => 340000000.00, 'date' => '2026-06-01'],
                            ],
                            'invoices' => [
                                ['amount' => 650000000.00, 'invoice_date' => '2026-02-01', 'status' => 'paid', 'payments' => [['amount' => 650000000.00, 'payment_date' => '2026-02-09']]],
                                ['amount' => 725000000.00, 'invoice_date' => '2026-06-20', 'status' => 'paid', 'payments' => [['amount' => 725000000.00, 'payment_date' => '2026-06-29']]],
                            ],
                            'fund_requests' => [
                                ['requested_by' => 'field', 'amount' => 95000000.00, 'status' => 'rejected'],
                            ],
                        ],
                    ],
                ],
            ];

            foreach ($clients as $clientSeed) {
                $clientId = $this->upsertClient($clientSeed['name'], [
                    'contact' => $clientSeed['contact'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $clientUserId = $this->upsertUser($clientSeed['client_user']['email'], [
                    'client_id' => $clientId,
                    'name' => $clientSeed['client_user']['name'],
                    'password' => Hash::make('password'),
                    'user_type' => 'client',
                    'employee_role' => null,
                    'email_verified_at' => $now,
                    'remember_token' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $projectTeamUsers = $jteUsers + ['client' => $clientUserId];

                foreach ($clientSeed['projects'] as $projectSeed) {
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

                    $this->seedProjectUsers($projectId, $projectSeed['team'], $projectTeamUsers);
                    $this->seedTender($projectSeed['tender'], $now);
                    $this->seedRab($projectId, $projectSeed['rab'], $now);
                    $this->seedRap($projectId, $projectSeed['rap'], $now);
                    $this->seedProgressReports($projectId, $projectSeed['progress_reports'], $now);
                    $this->seedProjectCosts($projectId, $projectSeed['project_costs']);
                    $this->seedInvoices($projectId, $projectSeed['invoices'], $now);
                    $this->seedFundRequests($projectId, $projectSeed['fund_requests'], $projectTeamUsers, $now);
                }
            }
        });

        $this->syncSeededUserRoles();
    }

    /**
     * @param array<string, mixed> $values
     */
    private function upsertClient(string $name, array $values): int
    {
        DB::table('clients')->updateOrInsert(
            ['name' => $name],
            $values
        );

        return (int) DB::table('clients')->where('name', $name)->value('id');
    }

    /**
     * @param array<string, mixed> $values
     */
    private function upsertUser(string $email, array $values): int
    {
        DB::table('users')->updateOrInsert(
            ['email' => $email],
            $values
        );

        return (int) DB::table('users')->where('email', $email)->value('id');
    }

    /**
     * @param array<string, mixed> $values
     */
    private function upsertProject(string $contractNumber, array $values): int
    {
        DB::table('projects')->updateOrInsert(
            ['contract_number' => $contractNumber],
            $values
        );

        return (int) DB::table('projects')->where('contract_number', $contractNumber)->value('id');
    }

    /**
     * @param array<int, array{user: string, role: string}> $teamSeeds
     * @param array<string, int> $users
     */
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

    /**
     * @param array<string, mixed> $tenderSeed
     */
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

    /**
     * @param array<string, mixed> $rabSeed
     */
    private function seedRab(int $projectId, array $rabSeed, mixed $now): void
    {
        DB::table('rabs')->updateOrInsert(
            ['project_id' => $projectId],
            [
                'total_budget' => $rabSeed['total_budget'],
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $rabId = (int) DB::table('rabs')->where('project_id', $projectId)->value('id');

        foreach ($rabSeed['items'] as $item) {
            DB::table('rab_items')->updateOrInsert(
                ['rab_id' => $rabId, 'description' => $item['description']],
                [
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                    'deleted_at' => null,
                ]
            );
        }
    }

    /**
     * @param array<string, mixed> $rapSeed
     */
    private function seedRap(int $projectId, array $rapSeed, mixed $now): void
    {
        DB::table('raps')->updateOrInsert(
            ['project_id' => $projectId],
            [
                'total_budget' => $rapSeed['total_budget'],
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $rapId = (int) DB::table('raps')->where('project_id', $projectId)->value('id');

        foreach ($rapSeed['items'] as $item) {
            DB::table('rap_items')->updateOrInsert(
                ['rap_id' => $rapId, 'description' => $item['description']],
                [
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                    'spec_brand' => $item['spec_brand'],
                    'spec_size' => $item['spec_size'],
                    'spec_strength' => $item['spec_strength'],
                    'deleted_at' => null,
                ]
            );
        }
    }

    /**
     * @param array<int, array<string, mixed>> $reportSeeds
     */
    private function seedProgressReports(int $projectId, array $reportSeeds, mixed $now): void
    {
        foreach ($reportSeeds as $reportSeed) {
            DB::table('progress_reports')->updateOrInsert(
                [
                    'project_id' => $projectId,
                    'report_date' => $reportSeed['report_date'],
                ],
                [
                    'progress_percent' => $reportSeed['progress_percent'],
                    'description' => $reportSeed['description'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $progressReportId = (int) DB::table('progress_reports')
                ->where('project_id', $projectId)
                ->where('report_date', $reportSeed['report_date'])
                ->value('id');

            DB::table('progress_approvals')->updateOrInsert(
                ['progress_report_id' => $progressReportId],
                [
                    'approved_by_client' => $reportSeed['approved_by_client'],
                    'approved_by_internal' => $reportSeed['approved_by_internal'],
                    'deleted_at' => null,
                ]
            );
        }
    }

    /**
     * @param array<int, array<string, mixed>> $costSeeds
     */
    private function seedProjectCosts(int $projectId, array $costSeeds): void
    {
        foreach ($costSeeds as $costSeed) {
            DB::table('project_costs')->updateOrInsert(
                [
                    'project_id' => $projectId,
                    'category' => $costSeed['category'],
                    'date' => $costSeed['date'],
                ],
                [
                    'amount' => $costSeed['amount'],
                    'deleted_at' => null,
                ]
            );
        }
    }

    /**
     * @param array<int, array<string, mixed>> $invoiceSeeds
     */
    private function seedInvoices(int $projectId, array $invoiceSeeds, mixed $now): void
    {
        foreach ($invoiceSeeds as $invoiceSeed) {
            DB::table('invoices')->updateOrInsert(
                [
                    'project_id' => $projectId,
                    'invoice_date' => $invoiceSeed['invoice_date'],
                ],
                [
                    'amount' => $invoiceSeed['amount'],
                    'status' => $invoiceSeed['status'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $invoiceId = (int) DB::table('invoices')
                ->where('project_id', $projectId)
                ->where('invoice_date', $invoiceSeed['invoice_date'])
                ->value('id');

            foreach ($invoiceSeed['payments'] as $paymentSeed) {
                DB::table('payments')->updateOrInsert(
                    [
                        'invoice_id' => $invoiceId,
                        'payment_date' => $paymentSeed['payment_date'],
                    ],
                    [
                        'amount' => $paymentSeed['amount'],
                        'deleted_at' => null,
                    ]
                );
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $fundRequestSeeds
     * @param array<string, int> $users
     */
    private function seedFundRequests(int $projectId, array $fundRequestSeeds, array $users, mixed $now): void
    {
        foreach ($fundRequestSeeds as $fundRequestSeed) {
            DB::table('fund_requests')->updateOrInsert(
                [
                    'project_id' => $projectId,
                    'requested_by' => $users[$fundRequestSeed['requested_by']],
                    'amount' => $fundRequestSeed['amount'],
                ],
                [
                    'status' => $fundRequestSeed['status'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    /**
     * @return array<int, string>
     */
    private function sidebarRoles(): array
    {
        return ['admin', 'employee', 'client', 'finance', 'marketing', 'operational', 'procurement', 'hr', 'management'];
    }

    private function syncSeededUserRoles(): void
    {
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
}
