<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plan d'Amélioration Continue — header. Spec: PHASE2_PROGRESS.md M6.5
 * + IMPLEMENTATION_PLAN line "PAC auto-generation from gaps".
 *
 * audit_run_id is nullable: a PAC may be auto-generated from a
 * finalised audit run OR authored manually (e.g. cross-cutting
 * improvement plan unrelated to a specific audit). nullOnDelete so
 * deleting an audit run preserves the PAC history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacs', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('audit_run_id')
                ->nullable()
                ->constrained('audit_runs')
                ->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            // PacStatus enum.
            $table->string('status')->default('draft');

            $table->string('target_period')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['structure_id', 'status']);
            $table->index(['structure_id', 'audit_run_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pacs');
    }
};
