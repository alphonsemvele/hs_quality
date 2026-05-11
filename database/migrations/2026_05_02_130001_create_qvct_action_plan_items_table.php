<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qvct_action_plan_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('action_plan_id')
                ->constrained('qvct_action_plans')
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->foreignId('responsible_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->date('due_date')->nullable();

            // QvctActionPlanItemStatus enum.
            $table->string('status')->default('pending');

            // Impact measurement — pre-set target the responsible should
            // hit, plus the actual figure recorded once measured.
            $table->text('impact_measurement_target')->nullable();
            $table->text('impact_measurement_actual')->nullable();
            $table->timestamp('impact_measured_at')->nullable();

            $table->timestamps();

            $table->index(['structure_id', 'action_plan_id']);
            $table->index(['structure_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qvct_action_plan_items');
    }
};
