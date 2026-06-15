<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table): void {
            if (! Schema::hasColumn('tenders', 'project_id')) {
                $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            }
            if (! Schema::hasColumn('tenders', 'document_number')) {
                $table->string('document_number', 120)->nullable();
            }
            if (! Schema::hasColumn('tenders', 'document_date')) {
                $table->date('document_date')->nullable();
            }
            if (! Schema::hasColumn('tenders', 'owner')) {
                $table->string('owner', 200)->nullable();
            }
            if (! Schema::hasColumn('tenders', 'location')) {
                $table->text('location')->nullable();
            }
            if (! Schema::hasColumn('tenders', 'notes')) {
                $table->text('notes')->nullable();
            }
        });

        Schema::table('progress_reports', function (Blueprint $table): void {
            if (! Schema::hasColumn('progress_reports', 'document_number')) {
                $table->string('document_number', 120)->nullable();
            }
            if (! Schema::hasColumn('progress_reports', 'document_type')) {
                $table->string('document_type', 80)->nullable();
            }
            if (! Schema::hasColumn('progress_reports', 'period_start')) {
                $table->date('period_start')->nullable();
            }
            if (! Schema::hasColumn('progress_reports', 'period_end')) {
                $table->date('period_end')->nullable();
            }
            if (! Schema::hasColumn('progress_reports', 'approved_by_internal')) {
                $table->boolean('approved_by_internal')->default(false);
            }
        });

        Schema::table('rabs', function (Blueprint $table): void {
            if (! Schema::hasColumn('rabs', 'document_number')) {
                $table->string('document_number', 120)->nullable();
            }
            if (! Schema::hasColumn('rabs', 'document_date')) {
                $table->date('document_date')->nullable();
            }
            if (! Schema::hasColumn('rabs', 'dpp_amount')) {
                $table->decimal('dpp_amount', 18, 2)->nullable();
            }
            if (! Schema::hasColumn('rabs', 'tax_amount')) {
                $table->decimal('tax_amount', 18, 2)->nullable();
            }
            if (! Schema::hasColumn('rabs', 'notes')) {
                $table->text('notes')->nullable();
            }
        });

        Schema::table('raps', function (Blueprint $table): void {
            if (! Schema::hasColumn('raps', 'document_number')) {
                $table->string('document_number', 120)->nullable();
            }
            if (! Schema::hasColumn('raps', 'document_date')) {
                $table->date('document_date')->nullable();
            }
            if (! Schema::hasColumn('raps', 'dpp_amount')) {
                $table->decimal('dpp_amount', 18, 2)->nullable();
            }
            if (! Schema::hasColumn('raps', 'tax_amount')) {
                $table->decimal('tax_amount', 18, 2)->nullable();
            }
            if (! Schema::hasColumn('raps', 'notes')) {
                $table->text('notes')->nullable();
            }
        });

        Schema::table('project_costs', function (Blueprint $table): void {
            if (! Schema::hasColumn('project_costs', 'reference_number')) {
                $table->string('reference_number', 120)->nullable();
            }
            if (! Schema::hasColumn('project_costs', 'vendor')) {
                $table->string('vendor', 200)->nullable();
            }
            if (! Schema::hasColumn('project_costs', 'description')) {
                $table->text('description')->nullable();
            }
        });

        Schema::table('invoices', function (Blueprint $table): void {
            if (! Schema::hasColumn('invoices', 'invoice_number')) {
                $table->string('invoice_number', 120)->nullable();
            }
            if (! Schema::hasColumn('invoices', 'due_date')) {
                $table->date('due_date')->nullable();
            }
            if (! Schema::hasColumn('invoices', 'tax_amount')) {
                $table->decimal('tax_amount', 18, 2)->nullable();
            }
            if (! Schema::hasColumn('invoices', 'description')) {
                $table->text('description')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn(['invoice_number', 'due_date', 'tax_amount', 'description']);
        });

        Schema::table('project_costs', function (Blueprint $table): void {
            $table->dropColumn(['reference_number', 'vendor', 'description']);
        });

        Schema::table('raps', function (Blueprint $table): void {
            $table->dropColumn(['document_number', 'document_date', 'dpp_amount', 'tax_amount', 'notes']);
        });

        Schema::table('rabs', function (Blueprint $table): void {
            $table->dropColumn(['document_number', 'document_date', 'dpp_amount', 'tax_amount', 'notes']);
        });

        Schema::table('progress_reports', function (Blueprint $table): void {
            $table->dropColumn([
                'document_number',
                'document_type',
                'period_start',
                'period_end',
                'approved_by_internal',
            ]);
        });

        Schema::table('tenders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('project_id');
            $table->dropColumn(['document_number', 'document_date', 'owner', 'location', 'notes']);
        });
    }
};
