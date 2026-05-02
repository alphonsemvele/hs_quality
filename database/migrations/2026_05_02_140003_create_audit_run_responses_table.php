<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-item response on a specific audit run. Spec: PHASE2_PROGRESS.md
 * M6.4 — "per-item score, comment, evidence file ref".
 *
 * Unique (audit_run_id, audit_grid_item_id) — exactly one response per
 * item per run; updates overwrite in place rather than accumulating.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_run_responses', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('audit_run_id')
                ->constrained('audit_runs')
                ->cascadeOnDelete();

            $table->foreignUuid('audit_grid_item_id')
                ->constrained('audit_grid_items')
                ->cascadeOnDelete();

            $table->decimal('score', 6, 2)->nullable();
            $table->text('comment')->nullable();

            // S3 path (or external URL) to the evidence file. The Phase 2
            // M6 PDF/document storage uses S3 SSE-KMS like the photo
            // pipeline; recording the path here lets the run-summary
            // surface signed-URL retrieval in the UI.
            $table->string('evidence_url')->nullable();

            $table->foreignId('recorded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('recorded_at')->nullable();

            $table->timestamps();

            $table->unique(['audit_run_id', 'audit_grid_item_id'], 'audit_response_unique_per_run_item');
            $table->index(['structure_id', 'audit_run_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_run_responses');
    }
};
