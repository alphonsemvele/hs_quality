<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * InterventionCompletedTask — records which planned tasks from the care
 * plan were actually executed during a given intervention.
 *
 * An intervenant ticks off tasks as they complete them. This table is
 * the link between "what was planned" (PlannedTask) and "what was done"
 * (Intervention), enabling coordinateurs to spot systematic omissions.
 *
 * structure_id is denormalized from the parent Intervention for fast
 * tenant-scoped queries and consistent BelongsToStructure enforcement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intervention_completed_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('intervention_id')
                ->constrained('interventions')
                ->cascadeOnDelete();

            $table->foreignUuid('planned_task_id')
                ->constrained('planned_tasks')
                ->cascadeOnDelete();

            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // A task can only be marked completed once per intervention.
            $table->unique(['intervention_id', 'planned_task_id']);

            $table->index(['structure_id', 'intervention_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intervention_completed_tasks');
    }
};
