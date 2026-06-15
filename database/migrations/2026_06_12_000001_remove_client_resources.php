<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'user_type')) {
            DB::table('users')
                ->where('user_type', 'client')
                ->update(['user_type' => 'employee']);

            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE users MODIFY user_type ENUM('employee','admin') NOT NULL DEFAULT 'employee'");
            }
        }

        if (Schema::hasColumn('projects', 'client_id')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('client_id');
            });
        }

        if (Schema::hasColumn('progress_reports', 'approved_by_client')) {
            Schema::table('progress_reports', function (Blueprint $table): void {
                $table->dropColumn('approved_by_client');
            });
        }

        if (Schema::hasColumn('progress_approvals', 'approved_by_client')) {
            Schema::table('progress_approvals', function (Blueprint $table): void {
                $table->dropColumn('approved_by_client');
            });
        }

        Schema::dropIfExists('clients');
    }

    public function down(): void
    {
        if (! Schema::hasTable('clients')) {
            Schema::create('clients', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 150)->nullable();
                $table->string('contact', 150)->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasColumn('projects', 'client_id')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('progress_reports', 'approved_by_client')) {
            Schema::table('progress_reports', function (Blueprint $table): void {
                $table->boolean('approved_by_client')->default(false)->after('description');
            });
        }

        if (! Schema::hasColumn('progress_approvals', 'approved_by_client')) {
            Schema::table('progress_approvals', function (Blueprint $table): void {
                $table->boolean('approved_by_client')->default(false)->after('progress_report_id');
            });
        }

        if (Schema::hasColumn('users', 'user_type') && DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY user_type ENUM('client','employee','admin') NOT NULL DEFAULT 'employee'");
        }
    }
};
