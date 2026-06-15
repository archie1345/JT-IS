<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\ProgressReport;
use App\Models\Project;
use App\Models\ProjectCost;
use App\Models\ProjectDocument;
use App\Models\Rab;
use App\Models\Rap;
use App\Models\Tender;
use App\Models\User;
use App\Support\AccessControl;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class TemporaryProjectMonitoringSeeder extends Seeder
{
    public function run(): void
    {
        AccessControl::sync();

        DB::transaction(function (): void {
            $users = [
                ['name' => 'Demo Admin', 'email' => 'admin@jte.com', 'user_type' => 'admin', 'employee_role' => 'System Admin', 'roles' => ['admin']],
                ['name' => 'Demo Operational', 'email' => 'operational@jte.com', 'user_type' => 'employee', 'employee_role' => 'operational', 'roles' => ['employee', 'operational']],
                ['name' => 'Demo Finance', 'email' => 'finance@jte.com', 'user_type' => 'employee', 'employee_role' => 'finance', 'roles' => ['employee', 'finance']],
                ['name' => 'Demo Management', 'email' => 'management@jte.com', 'user_type' => 'employee', 'employee_role' => 'management', 'roles' => ['employee', 'management']],
            ];

            foreach ($users as $seed) {
                $roles = $seed['roles'];
                unset($seed['roles']);

                $user = User::query()->updateOrCreate(
                    ['email' => $seed['email']],
                    array_merge($seed, [
                        'password' => Hash::make('password123'),
                        'email_verified_at' => now(),
                    ]),
                );
                $user->syncRoles($roles);
            }

            $ownerA = 'Perum Jasa Tirta I';
            $ownerB = 'PT Kawasan Energi Nusantara';

            Tender::query()->create([
                'title' => 'Rehabilitasi Panel Kontrol PLTM',
                'owner' => $ownerA,
                'value' => 950000000,
                'status' => 'open',
                'document_number' => 'PNW/OPEN/001',
                'document_date' => now()->subDays(12),
            ]);

            Tender::query()->create([
                'title' => 'Pekerjaan Intake Pompa Sungai',
                'owner' => $ownerB,
                'value' => 1450000000,
                'status' => 'submitted',
                'document_number' => 'PNW/SUB/002',
                'document_date' => now()->subDays(8),
            ]);

            $projectA = $this->project('Project A - Revitalisasi SCADA Bendungan', 1800000000, now()->subMonth(), now()->addMonths(5), 'ongoing');
            $projectB = $this->project('Project B - Penguatan Jaringan Distribusi', 2200000000, now()->subMonths(2), now()->addMonths(3), 'ongoing');
            $projectC = $this->project('Project C - Perbaikan Turbin Minihidro', 1250000000, now()->subMonths(6), now()->subDays(10), 'ongoing');

            Tender::query()->create([
                'project_id' => $projectA->id,
                'title' => 'Revitalisasi SCADA Bendungan',
                'owner' => $ownerA,
                'value' => 1800000000,
                'status' => 'won',
                'document_number' => 'PNW/WON/003',
                'document_date' => now()->subDays(35),
            ]);

            $this->seedBudget($projectA, 1750000000, 1500000000);
            $this->seedBudget($projectB, 2150000000, 1800000000);
            $this->seedBudget($projectC, 1200000000, 1000000000);

            $this->progress($projectA, 50, true, 'BA MC 50% disetujui.');
            $this->progress($projectB, 75, true, 'BA MC 75% disetujui.');
            $this->progress($projectC, 75, true, 'BA MC 75% disetujui.');
            $this->progress($projectC, 90, false, 'Draft progress lapangan 90%, belum disetujui internal.');

            Storage::disk('public')->put(
                'projects/'.$projectA->id.'/project/demo-bamc.txt',
                "BA MC 50%\nProgress fisik pekerjaan Revitalisasi SCADA Bendungan telah disetujui internal.",
            );
            ProjectDocument::query()->create([
                'project_id' => $projectA->id,
                'document_type' => 'bamc',
                'component_type' => 'progress_report',
                'name' => 'Demo BAMC 50 Persen',
                'original_name' => 'demo-bamc-50.txt',
                'path' => 'projects/'.$projectA->id.'/project/demo-bamc.txt',
                'mime_type' => 'text/plain',
                'size' => 96,
                'ocr_text' => "BA MC 50%\nProgress fisik pekerjaan Revitalisasi SCADA Bendungan telah disetujui internal.",
                'ocr_engine' => 'seeded-demo',
                'ocr_processed_at' => now(),
            ]);

            ProjectCost::query()->create(['project_id' => $projectA->id, 'category' => 'Material', 'amount' => 650000000, 'date' => now()->subDays(15)]);
            ProjectCost::query()->create(['project_id' => $projectB->id, 'category' => 'Subcontractor', 'amount' => 1650000000, 'date' => now()->subDays(10)]);
            ProjectCost::query()->create(['project_id' => $projectC->id, 'category' => 'Equipment', 'amount' => 1100000000, 'date' => now()->subDays(20)]);

            $invoiceA = Invoice::query()->create([
                'project_id' => $projectA->id,
                'invoice_number' => 'INV/A/001',
                'amount' => 600000000,
                'invoice_date' => now()->subDays(7),
                'due_date' => now()->addDays(14),
                'status' => 'pending',
                'description' => 'Tagihan termin progress 50%.',
            ]);
            $invoiceA->payments()->create(['amount' => 250000000, 'payment_date' => now()->subDays(2)]);

            Invoice::query()->create([
                'project_id' => $projectB->id,
                'invoice_number' => 'INV/B/001',
                'amount' => 1200000000,
                'invoice_date' => now()->subDays(18),
                'due_date' => now()->addDays(5),
                'status' => 'pending',
                'description' => 'Tagihan termin progress 75%.',
            ]);

            Invoice::query()->create([
                'project_id' => $projectC->id,
                'invoice_number' => 'INV/C/001',
                'amount' => 800000000,
                'invoice_date' => now()->subDays(45),
                'due_date' => now()->subDays(15),
                'status' => 'overdue',
                'description' => 'Tagihan jatuh tempo.',
            ]);
        });
    }

    private function project(string $name, float $value, mixed $start, mixed $end, string $status): Project
    {
        return Project::query()->create([
            'name' => $name,
            'contract_number' => 'JTE/'.str_pad((string) (Project::query()->count() + 1), 3, '0', STR_PAD_LEFT).'/2026',
            'contract_value' => $value,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'location' => 'Jawa Timur',
            'status' => $status,
        ]);
    }

    private function seedBudget(Project $project, float $rabTotal, float $rapTotal): void
    {
        $rab = Rab::query()->create(['project_id' => $project->id, 'document_number' => 'RAB-'.$project->id]);
        $rab->items()->createMany([
            ['description' => 'Pekerjaan utama', 'quantity' => 1, 'unit' => 'ls', 'unit_price' => $rabTotal * 0.7, 'total_price' => $rabTotal * 0.7],
            ['description' => 'Material dan instalasi', 'quantity' => 1, 'unit' => 'ls', 'unit_price' => $rabTotal * 0.3, 'total_price' => $rabTotal * 0.3],
        ]);
        $rab->update(['total_budget' => $rab->items()->sum('total_price')]);

        $rap = Rap::query()->create(['project_id' => $project->id, 'document_number' => 'RPA-'.$project->id]);
        $rap->items()->createMany([
            ['description' => 'Pelaksanaan lapangan', 'quantity' => 1, 'unit' => 'ls', 'unit_price' => $rapTotal * 0.65, 'total_price' => $rapTotal * 0.65],
            ['description' => 'Pengadaan dan logistik', 'quantity' => 1, 'unit' => 'ls', 'unit_price' => $rapTotal * 0.35, 'total_price' => $rapTotal * 0.35],
        ]);
        $rap->update(['total_budget' => $rap->items()->sum('total_price')]);
    }

    private function progress(Project $project, float $percent, bool $internalApproved, string $description): ProgressReport
    {
        return ProgressReport::query()->create([
            'project_id' => $project->id,
            'document_number' => 'BAMC-'.$project->id.'-'.$percent,
            'document_type' => 'BA MC',
            'progress_percent' => $percent,
            'report_date' => now()->subDays((int) max(1, 100 - $percent)),
            'description' => $description,
            'approved_by_internal' => $internalApproved,
        ]);
    }
}
