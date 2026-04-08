<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TemporaryProjectMonitoringSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $now = now();

            $clientId = DB::table('clients')->updateOrInsert(
                ['name' => 'PT Nusantara Konstruksi'],
                [
                    'contact' => 'client@nusantara.test',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            )
                ? DB::table('clients')->where('name', 'PT Nusantara Konstruksi')->value('id')
                : DB::table('clients')->where('name', 'PT Nusantara Konstruksi')->value('id');

            $managerId = $this->upsertUser(
                email: 'manager@jte.test',
                values: [
                    'client_id' => null,
                    'name' => 'JTE Project Manager',
                    'password' => Hash::make('password'),
                    'user_type' => 'jte',
                    'email_verified_at' => $now,
                    'remember_token' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $financeId = $this->upsertUser(
                email: 'finance@jte.test',
                values: [
                    'client_id' => null,
                    'name' => 'JTE Finance',
                    'password' => Hash::make('password'),
                    'user_type' => 'jte',
                    'email_verified_at' => $now,
                    'remember_token' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $clientUserId = $this->upsertUser(
                email: 'client@nusantara.test',
                values: [
                    'client_id' => $clientId,
                    'name' => 'Nusantara Client PIC',
                    'password' => Hash::make('password'),
                    'user_type' => 'client',
                    'email_verified_at' => $now,
                    'remember_token' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            DB::table('projects')->updateOrInsert(
                ['contract_number' => 'JTE/PM/2026/001'],
                [
                    'client_id' => $clientId,
                    'name' => 'Pembangunan Gudang Distribusi',
                    'contract_value' => 2750000000.00,
                    'start_date' => '2026-04-01',
                    'end_date' => '2026-11-30',
                    'location' => 'Bekasi, Jawa Barat',
                    'status' => 'ongoing',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $projectId = DB::table('projects')
                ->where('contract_number', 'JTE/PM/2026/001')
                ->value('id');

            DB::table('project_users')->updateOrInsert(
                ['project_id' => $projectId, 'user_id' => $managerId],
                ['role' => 'manager']
            );

            DB::table('project_users')->updateOrInsert(
                ['project_id' => $projectId, 'user_id' => $financeId],
                ['role' => 'finance']
            );

            DB::table('project_users')->updateOrInsert(
                ['project_id' => $projectId, 'user_id' => $clientUserId],
                ['role' => 'client']
            );

            DB::table('tenders')->updateOrInsert(
                ['title' => 'Tender Gudang Distribusi Bekasi'],
                [
                    'value' => 2800000000.00,
                    'status' => 'won',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            DB::table('rabs')->updateOrInsert(
                ['project_id' => $projectId],
                [
                    'total_budget' => 2400000000.00,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $rabId = DB::table('rabs')->where('project_id', $projectId)->value('id');

            DB::table('rab_items')->updateOrInsert(
                ['rab_id' => $rabId, 'description' => 'Pekerjaan Struktur Beton'],
                [
                    'quantity' => 1,
                    'unit_price' => 1200000000.00,
                    'total_price' => 1200000000.00,
                ]
            );

            DB::table('raps')->updateOrInsert(
                ['project_id' => $projectId],
                [
                    'total_budget' => 2250000000.00,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $rapId = DB::table('raps')->where('project_id', $projectId)->value('id');

            DB::table('rap_items')->updateOrInsert(
                ['rap_id' => $rapId, 'description' => 'Ready Mix Concrete'],
                [
                    'quantity' => 250,
                    'unit_price' => 950000.00,
                    'total_price' => 237500000.00,
                    'spec_brand' => 'Merah Beton',
                    'spec_size' => 'm3',
                    'spec_strength' => 'K-350',
                ]
            );

            DB::table('progress_reports')->updateOrInsert(
                ['project_id' => $projectId, 'report_date' => '2026-04-07'],
                [
                    'progress_percent' => 18,
                    'description' => 'Mobilisasi tim dan pekerjaan pondasi awal telah berjalan.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $progressReportId = DB::table('progress_reports')
                ->where('project_id', $projectId)
                ->where('report_date', '2026-04-07')
                ->value('id');

            DB::table('progress_approvals')->updateOrInsert(
                ['progress_report_id' => $progressReportId],
                [
                    'approved_by_client' => true,
                    'approved_by_internal' => true,
                ]
            );

            DB::table('project_costs')->updateOrInsert(
                ['project_id' => $projectId, 'category' => 'Mobilization'],
                [
                    'amount' => 85000000.00,
                    'date' => '2026-04-05',
                ]
            );

            DB::table('invoices')->updateOrInsert(
                ['project_id' => $projectId, 'invoice_date' => '2026-04-08'],
                [
                    'amount' => 350000000.00,
                    'status' => 'pending',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $invoiceId = DB::table('invoices')
                ->where('project_id', $projectId)
                ->where('invoice_date', '2026-04-08')
                ->value('id');

            DB::table('payments')->updateOrInsert(
                ['invoice_id' => $invoiceId, 'payment_date' => '2026-04-09'],
                ['amount' => 150000000.00]
            );

            DB::table('fund_requests')->updateOrInsert(
                ['project_id' => $projectId, 'requested_by' => $managerId, 'amount' => 125000000.00],
                [
                    'status' => 'approved_manager',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        });
    }

    /**
     * Insert or update a user and return its id.
     *
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
}
