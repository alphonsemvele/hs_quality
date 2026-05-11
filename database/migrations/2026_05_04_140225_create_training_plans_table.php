<?php

use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Annual training plan, one per structure per year. Spec:
 * PHASE2_PROGRESS.md M5.3.
 *
 * Status lifecycle (draft → published → archived) is enforced by
 * TrainingPlanService — a published plan cannot be re-drafted, an
 * archived one is immutable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_plans', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('year'); // e.g. 2026
            $table->string('theme'); // e.g. "Bientraitance et HAS 2025"
            $table->text('target_audience')->nullable(); // free text
            $table->string('status')->default('draft'); // TrainingPlanStatus

            $table->foreignIdFor(User::class, 'created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // One plan per (structure, year) is the common case but not
            // strictly required (structure may run mid-year refresh).
            $table->index(['structure_id', 'year']);
            $table->index(['structure_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_plans');
    }
};
