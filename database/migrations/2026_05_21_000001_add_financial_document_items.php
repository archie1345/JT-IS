<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('source_type', 20)->nullable();
            $table->unsignedBigInteger('source_item_id')->nullable();
            $table->string('category', 150)->nullable();
            $table->string('description', 255)->nullable();
            $table->string('unit', 50)->nullable();
            $table->decimal('quantity', 10, 2)->nullable();
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->decimal('total_price', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->index(['source_type', 'source_item_id'], 'invoice_items_source_idx');
        });

        Schema::create('project_cost_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_cost_id')->constrained('project_costs')->cascadeOnDelete();
            $table->string('source_type', 20)->nullable();
            $table->unsignedBigInteger('source_item_id')->nullable();
            $table->string('category', 150)->nullable();
            $table->string('description', 255)->nullable();
            $table->string('unit', 50)->nullable();
            $table->decimal('quantity', 10, 2)->nullable();
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->decimal('total_price', 15, 2)->nullable();
            $table->string('vendor', 200)->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->index(['source_type', 'source_item_id'], 'project_cost_items_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_cost_items');
        Schema::dropIfExists('invoice_items');
    }
};
