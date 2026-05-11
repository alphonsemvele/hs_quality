<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Execution of an audit grid against a structure on a date.
 * Spec: PHASE2_PROGRESS.md M6.3.
 *
 * Score and max_score frozen at finalise time so a later edit of the
 * underlying grid (item added / weight changed) doesn't silently
 * mutate historical scores.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_runs', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('audit_grid_id')
                ->constrained('audit_grids')
                ->cascadeOnDelete();

            $table->string('title');
            $table->date('run_date');

            // AuditRunStatus enum.
            $table->string('status')->default('draft');

            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('max_score', 8, 2)->nullable();

            $table->foreignId('finalised_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('finalised_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['structure_id', 'audit_grid_id', 'status']);
            $table->index(['structure_id', 'run_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_runs');
    }
};
