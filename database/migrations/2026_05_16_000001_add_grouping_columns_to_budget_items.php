<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['rab_items', 'rap_items'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('category', 150)->nullable()->after('id');
                $table->string('sub_category', 150)->nullable()->after('category');
                $table->string('unit', 50)->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        foreach (['rab_items', 'rap_items'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['category', 'sub_category', 'unit']);
            });
        }
    }
};
