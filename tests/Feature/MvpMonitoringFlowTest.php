<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectCost;
use App\Models\Rab;
use App\Models\Rap;
use App\Models\Tender;
use App\Models\User;

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

test('project health becomes warning and critical from mvp thresholds', function () {
    $project = Project::query()->create([
        'name' => 'Health Demo',
        'contract_value' => 1000000,
        'status' => 'ongoing',
    ]);

    $rap = Rap::query()->create(['project_id' => $project->id]);
    $rap->items()->create(['description' => 'Execution', 'quantity' => 1, 'unit_price' => 1000, 'total_price' => 1000]);

    ProjectCost::query()->create(['project_id' => $project->id, 'amount' => 900, 'date' => now()]);
    expect($project->fresh()->mvpStatus())->toBe('Warning');

    ProjectCost::query()->create(['project_id' => $project->id, 'amount' => 101, 'date' => now()]);
    expect($project->fresh()->mvpStatus())->toBe('Critical');
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
            ->component('Dashboard')
            ->where('dashboardData.problemProjects.0.name', 'Overdue Invoice Demo')
        );
});

test('admin can access mvp routes', function () {
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
