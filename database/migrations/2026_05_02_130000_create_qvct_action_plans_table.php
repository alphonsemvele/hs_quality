<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QVCT action plan — RH-driven plan with measurable impact targets.
 * Spec: PHASE2_PROGRESS.md M3.28 + CDC §M3 line "QVCT action plan with
 * impact measurement".
 *
 * The plan itself is a header; items live in qvct_action_plan_items.
 * `target_quarter` is a free-text label like "Q3-2026" so RH can group
 * plans visually without coupling to a date column (a plan may span
 * multiple quarters in practice).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qvct_action_plans', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            // QvctActionPlanStatus enum.
            $table->string('status')->default('draft');

            $table->string('target_quarter')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('published_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('closed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['structure_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qvct_action_plans');
    }
};
