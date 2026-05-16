<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->decimal('contract_value', 20, 2)->nullable()->change();
        });

        Schema::table('rabs', function (Blueprint $table): void {
            $table->decimal('total_budget', 20, 2)->nullable()->change();
        });

        Schema::table('rab_items', function (Blueprint $table): void {
            $table->decimal('unit_price', 20, 2)->nullable()->change();
            $table->decimal('total_price', 20, 2)->nullable()->change();
        });

        Schema::table('raps', function (Blueprint $table): void {
            $table->decimal('total_budget', 20, 2)->nullable()->change();
        });

        Schema::table('rap_items', function (Blueprint $table): void {
            $table->decimal('unit_price', 20, 2)->nullable()->change();
            $table->decimal('total_price', 20, 2)->nullable()->change();
        });

        Schema::table('tenders', function (Blueprint $table): void {
            $table->decimal('value', 20, 2)->nullable()->change();
        });

        Schema::table('project_costs', function (Blueprint $table): void {
            $table->decimal('amount', 20, 2)->nullable()->change();
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->decimal('amount', 20, 2)->nullable()->change();
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->decimal('amount', 20, 2)->nullable()->change();
        });

        Schema::table('fund_requests', function (Blueprint $table): void {
            $table->decimal('amount', 20, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->decimal('contract_value', 15, 2)->nullable()->change();
        });

        Schema::table('rabs', function (Blueprint $table): void {
            $table->decimal('total_budget', 15, 2)->nullable()->change();
        });

        Schema::table('rab_items', function (Blueprint $table): void {
            $table->decimal('unit_price', 15, 2)->nullable()->change();
            $table->decimal('total_price', 15, 2)->nullable()->change();
        });

        Schema::table('raps', function (Blueprint $table): void {
            $table->decimal('total_budget', 15, 2)->nullable()->change();
        });

        Schema::table('rap_items', function (Blueprint $table): void {
            $table->decimal('unit_price', 15, 2)->nullable()->change();
            $table->decimal('total_price', 15, 2)->nullable()->change();
        });

        Schema::table('tenders', function (Blueprint $table): void {
            $table->decimal('value', 15, 2)->nullable()->change();
        });

        Schema::table('project_costs', function (Blueprint $table): void {
            $table->decimal('amount', 15, 2)->nullable()->change();
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->decimal('amount', 15, 2)->nullable()->change();
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->decimal('amount', 15, 2)->nullable()->change();
        });

        Schema::table('fund_requests', function (Blueprint $table): void {
            $table->decimal('amount', 15, 2)->nullable()->change();
        });
    }
};
