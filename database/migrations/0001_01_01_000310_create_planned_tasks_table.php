<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Planned tasks — recurring activities within a care plan (e.g., "morning
 * hygiene", "blood pressure check", "shopping assistance").
 *
 * structure_id is denormalized from care_plans.structure_id so:
 *   - Eloquent queries can be tenant-scoped via BelongsToStructure trait
 *     without needing to JOIN care_plans on every read
 *   - Indexes are smaller and queries faster
 *
 * The model boot() guarantees structure_id always matches the parent
 * care_plan's structure_id; a unit test verifies this invariant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planned_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignUuid('care_plan_id')
                ->constrained('care_plans')
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->string('frequency', 20)->default('daily');
            $table->jsonb('frequency_details')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();

            $table->unsignedSmallInteger('task_order')->default(0);
            $table->boolean('mandatory')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['structure_id', 'care_plan_id', 'task_order']);
            $table->index(['care_plan_id', 'task_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planned_tasks');
    }
};
