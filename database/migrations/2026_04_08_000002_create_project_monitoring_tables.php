<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->nullable();
            $table->string('contact', 150)->nullable();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('name', 200)->nullable();
            $table->string('contract_number', 100)->nullable();
            $table->decimal('contract_value', 15, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('location')->nullable();
            $table->enum('status', ['planning', 'ongoing', 'completed'])->default('planning');
            $table->timestamps();
            $table->index('status', 'idx_project_status');
        });

        Schema::create('project_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role', ['manager', 'finance', 'field', 'director', 'client']);
        });

        Schema::create('tenders', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200)->nullable();
            $table->decimal('value', 15, 2)->nullable();
            $table->enum('status', ['open', 'submitted', 'won', 'lost'])->nullable();
            $table->timestamps();
        });

        Schema::create('rabs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->decimal('total_budget', 15, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('rab_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rab_id')->constrained('rabs')->cascadeOnDelete();
            $table->string('description', 255)->nullable();
            $table->decimal('quantity', 10, 2)->nullable();
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->decimal('total_price', 15, 2)->nullable();
        });

        Schema::create('raps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->decimal('total_budget', 15, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('rap_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rap_id')->constrained('raps')->cascadeOnDelete();
            $table->string('description', 255)->nullable();
            $table->decimal('quantity', 10, 2)->nullable();
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->decimal('total_price', 15, 2)->nullable();
            $table->string('spec_brand', 100)->nullable();
            $table->string('spec_size', 100)->nullable();
            $table->string('spec_strength', 100)->nullable();
        });

        Schema::create('progress_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->integer('progress_percent')->nullable();
            $table->date('report_date')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index('project_id', 'idx_progress_project');
        });

        Schema::create('progress_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('progress_report_id')->constrained('progress_reports')->cascadeOnDelete();
            $table->boolean('approved_by_client')->default(false);
            $table->boolean('approved_by_internal')->default(false);
        });

        Schema::create('project_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('category', 100)->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->date('date')->nullable();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->nullable();
            $table->date('invoice_date')->nullable();
            $table->enum('status', ['pending', 'paid', 'overdue'])->nullable();
            $table->timestamps();
            $table->index('status', 'idx_invoice_status');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->nullable();
            $table->date('payment_date')->nullable();
        });

        Schema::create('fund_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->nullable();
            $table->enum('status', ['pending', 'approved_manager', 'approved_finance', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_requests');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('project_costs');
        Schema::dropIfExists('progress_approvals');
        Schema::dropIfExists('progress_reports');
        Schema::dropIfExists('rap_items');
        Schema::dropIfExists('raps');
        Schema::dropIfExists('rab_items');
        Schema::dropIfExists('rabs');
        Schema::dropIfExists('tenders');
        Schema::dropIfExists('project_users');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('clients');
    }
};
