<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_documents', function (Blueprint $table): void {
            $table->longText('ocr_text')->nullable()->after('size');
            $table->string('ocr_engine', 100)->nullable()->after('ocr_text');
            $table->timestamp('ocr_processed_at')->nullable()->after('ocr_engine');
        });
    }

    public function down(): void
    {
        Schema::table('project_documents', function (Blueprint $table): void {
            $table->dropColumn(['ocr_text', 'ocr_engine', 'ocr_processed_at']);
        });
    }
};
