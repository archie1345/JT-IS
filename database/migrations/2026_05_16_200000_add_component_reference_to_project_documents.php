<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_documents', function (Blueprint $table): void {
            $table->string('component_type', 80)->nullable()->after('document_type');
            $table->unsignedBigInteger('component_id')->nullable()->after('component_type');
            $table->index(['project_id', 'component_type', 'component_id'], 'project_docs_component_idx');
        });
    }

    public function down(): void
    {
        Schema::table('project_documents', function (Blueprint $table): void {
            $table->dropIndex('project_docs_component_idx');
            $table->dropColumn(['component_type', 'component_id']);
        });
    }
};
