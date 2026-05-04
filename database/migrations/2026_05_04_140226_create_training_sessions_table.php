<?php

use App\Models\Structure;
use App\Models\TrainingPlan;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Scheduled training session under a plan. Spec: PHASE2_PROGRESS.md M5.4.
 *
 * `trainer_user_id` is nullable — many sessions are external (e.g. CFA
 * Croix-Rouge). When external, `trainer_name` carries the human label.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(TrainingPlan::class, 'training_plan_id')
                ->constrained('training_plans')
                ->cascadeOnDelete();

            $table->string('title');
            $table->string('trainer_name')->nullable();
            $table->foreignIdFor(User::class, 'trainer_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->unsignedSmallInteger('capacity')->default(20);
            $table->string('location')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['structure_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_sessions');
    }
};
