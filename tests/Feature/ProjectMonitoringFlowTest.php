<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectCost;
use App\Models\ProgressReport;
use App\Models\Rab;
use App\Models\Rap;
use App\Models\Tender;
use App\Models\User;
use Illuminate\Http\UploadedFile;

test('won tender can be converted to a project once', function () {
    $this->actingAs(User::factory()->admin()->create());

    $tender = Tender::query()->create([
        'title' => 'Tender Demo Won',
        'owner' => 'PT Demo Client',
        'value' => 1000000,
        'status' => 'won',
    ]);

    $this->post(route('pipeline.convert', $tender->id))
        ->assertRedirect();

    $tender->refresh();
    expect($tender->project_id)->not->toBeNull();
    expect($tender->project->name)->toBe('Tender Demo Won');
    expect((float) $tender->project->contract_value)->toBe(1000000.0);

    $this->post(route('pipeline.convert', $tender->id))
        ->assertRedirect(route('projects.show', $tender->project_id));

    expect(Project::query()->where('name', 'Tender Demo Won')->count())->toBe(1);
});

test('rab and rap totals use item sums', function () {
    $project = Project::query()->create([
        'name' => 'Budget Total Demo',
        'contract_value' => 1000000,
        'status' => 'ongoing',
    ]);

    $rab = Rab::query()->create(['project_id' => $project->id]);
    $rab->items()->create(['description' => 'A', 'quantity' => 2, 'unit_price' => 100, 'total_price' => 200]);
    $rab->items()->create(['description' => 'B', 'quantity' => 1, 'unit_price' => 300, 'total_price' => 300]);

    $rap = Rap::query()->create(['project_id' => $project->id]);
    $rap->items()->create(['description' => 'A', 'quantity' => 1, 'unit_price' => 250, 'total_price' => 250]);
    $rap->items()->create(['description' => 'B', 'quantity' => 3, 'unit_price' => 50, 'total_price' => 150]);

    expect($project->rabTotal())->toBe(500.0);
    expect($project->rapTotal())->toBe(400.0);
});

test('project health becomes warning and critical from health thresholds', function () {
    $project = Project::query()->create([
        'name' => 'Health Demo',
        'contract_value' => 1000000,
        'status' => 'ongoing',
    ]);

    $rap = Rap::query()->create(['project_id' => $project->id]);
    $rap->items()->create(['description' => 'Execution', 'quantity' => 1, 'unit_price' => 1000, 'total_price' => 1000]);

    ProjectCost::query()->create(['project_id' => $project->id, 'amount' => 900, 'date' => now()]);
    expect($project->fresh()->projectHealthStatus())->toBe('Warning');

    ProjectCost::query()->create(['project_id' => $project->id, 'amount' => 101, 'date' => now()]);
    expect($project->fresh()->projectHealthStatus())->toBe('Critical');
});

test('project detail save cannot change the progress snapshot percentage', function () {
    $this->actingAs(User::factory()->admin()->create());

    $client = Client::query()->create(['name' => 'Snapshot Client']);
    $project = Project::query()->create([
        'client_id' => $client->id,
        'name' => 'Snapshot Guard Demo',
        'contract_value' => 1000000,
        'status' => 'ongoing',
    ]);

    $report = ProgressReport::query()->create([
        'project_id' => $project->id,
        'progress_percent' => 35,
        'report_date' => '2026-05-01',
        'description' => 'Original BAMC progress',
    ]);

    $this->patch(route('projects.update', $project), [
        'client_id' => $client->id,
        'name' => 'Snapshot Guard Updated',
        'contract_value' => 1500000,
        'status' => 'completed',
        'payment_status' => 'paid',
        'progress_percent' => 90,
        'progress_note' => 'Should be ignored outside BAMC',
    ])->assertRedirect(route('projects.show', $project));

    $report->refresh();

    expect((float) $report->progress_percent)->toBe(35.0);
    expect($report->description)->toBe('Original BAMC progress');
    expect(ProgressReport::query()->where('project_id', $project->id)->count())->toBe(1);
});

test('bamc progress update can change the progress snapshot percentage', function () {
    $this->actingAs(User::factory()->admin()->create());

    $project = Project::query()->create([
        'name' => 'BAMC Snapshot Demo',
        'contract_value' => 1000000,
        'status' => 'ongoing',
    ]);

    $report = ProgressReport::query()->create([
        'project_id' => $project->id,
        'progress_percent' => 35,
        'report_date' => '2026-05-01',
    ]);

    $this->patch(route('progress-updates.update', $report->id), [
        'progress_percent' => 90,
    ])->assertRedirect();

    expect((float) $report->refresh()->progress_percent)->toBe(90.0);
});

test('overdue invoice appears in dashboard warning data', function () {
    $this->actingAs(User::factory()->admin()->create());

    $client = Client::query()->create(['name' => 'Client Warning']);
    $project = Project::query()->create([
        'client_id' => $client->id,
        'name' => 'Overdue Invoice Demo',
        'contract_value' => 1000000,
        'status' => 'ongoing',
    ]);

    Invoice::query()->create([
        'project_id' => $project->id,
        'amount' => 100000,
        'invoice_date' => now()->subDays(10),
        'due_date' => now()->subDay(),
        'status' => 'overdue',
    ]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->where('dashboardData.problemProjects.0.name', 'Overdue Invoice Demo')
        );
});

test('ocr route rejects invalid files', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->postJson(route('projects.documents.ocr'), [
        'file' => UploadedFile::fake()->create('payload.exe', 1, 'application/x-msdownload'),
    ])->assertUnprocessable();
});

test('ocr route handles provider not configured gracefully', function () {
    $this->actingAs(User::factory()->admin()->create());
    config(['ocr.provider' => 'none']);

    $this->postJson(route('projects.documents.ocr'), [
        'file' => UploadedFile::fake()->create('bamc.jpg', 10, 'image/jpeg'),
    ])
        ->assertStatus(503)
        ->assertJsonPath('message', 'OCR belum dikonfigurasi. Silakan input manual atau hubungi admin.');
});

test('rab ocr apply creates items only after explicit apply', function () {
    $this->actingAs(User::factory()->admin()->create());

    $project = Project::query()->create([
        'name' => 'OCR RAB Apply Demo',
        'contract_value' => 1000000,
        'status' => 'ongoing',
    ]);
    $rab = Rab::query()->create(['project_id' => $project->id]);

    expect($rab->items()->count())->toBe(0);

    $this->postJson(route('projects.documents.apply-extraction'), [
        'project_id' => $project->id,
        'component_type' => 'rab',
        'component_id' => $rab->id,
        'items' => [
            [
                'description' => 'Pekerjaan persiapan',
                'quantity' => 2,
                'unit' => 'ls',
                'unit_price' => 100000,
                'total_price' => 200000,
            ],
        ],
    ])
        ->assertOk()
        ->assertJsonPath('items_created', 1);

    expect($rab->items()->count())->toBe(1);
    expect((float) $rab->refresh()->total_budget)->toBe(200000.0);
});

test('invoice ocr apply respects approved progress billing validation', function () {
    $this->actingAs(User::factory()->admin()->create());

    $project = Project::query()->create([
        'name' => 'OCR Invoice Guard Demo',
        'contract_value' => 1000000,
        'status' => 'ongoing',
    ]);

    $this->postJson(route('projects.documents.apply-extraction'), [
        'project_id' => $project->id,
        'component_type' => 'invoice',
        'amount' => 100000,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('amount');

    expect(Invoice::query()->where('project_id', $project->id)->count())->toBe(0);
});

test('progress ocr apply does not auto approve bamc suggestions', function () {
    $this->actingAs(User::factory()->admin()->create());

    $project = Project::query()->create([
        'name' => 'OCR Progress Guard Demo',
        'contract_value' => 1000000,
        'status' => 'ongoing',
    ]);

    $this->postJson(route('projects.documents.apply-extraction'), [
        'project_id' => $project->id,
        'component_type' => 'progress_report',
        'progress_percent' => 80,
    ])->assertOk();

    $report = ProgressReport::query()->where('project_id', $project->id)->first();

    expect((float) $report->progress_percent)->toBe(80.0);
    expect((bool) $report->approved_by_client)->toBeFalse();
    expect((bool) $report->approved_by_internal)->toBeFalse();
});

test('admin can access monitoring routes', function () {
    $this->actingAs(User::factory()->admin()->create());

    $project = Project::query()->create([
        'name' => 'Route Demo',
        'contract_value' => 1000000,
        'status' => 'ongoing',
    ]);

    $rab = Rab::query()->create(['project_id' => $project->id]);
    $rap = Rap::query()->create(['project_id' => $project->id]);

    $this->get(route('dashboard'))->assertOk();
    $this->get(route('pipeline'))->assertOk();
    $this->get(route('projects'))->assertOk();
    $this->get(route('projects.show', $project))->assertOk();
    $this->get(route('rabs'))->assertOk();
    $this->get(route('rabs.show', $rab))->assertOk();
    $this->get(route('raps'))->assertOk();
    $this->get(route('raps.show', $rap))->assertOk();
    $this->get(route('progress-updates.index'))->assertOk();
    $this->get(route('invoices.index'))->assertOk();
    $this->get(route('project-costs.index'))->assertOk();
});
