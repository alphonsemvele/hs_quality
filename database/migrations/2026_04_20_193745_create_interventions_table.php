<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Intervention — a single visit record linking an intervenant to a
 * beneficiary on a specific date. Covers both planned (coordinateur-
 * created) and ad-hoc (intervenant self-logged) visits.
 *
 * Lifecycle: planned → in_progress (check-in) → completed (check-out)
 *            planned / in_progress → cancelled
 *            planned → missed (manual or batch when planned_date passes)
 *
 * GPS fields are nullable because web-created visits have no coordinates;
 * only mobile check-ins produce location data.
 *
 * report_text and report_voice_transcript are encrypted at rest because
 * they may contain medical observations (RGPD Art 9 health data).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interventions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('intervenant_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignUuid('beneficiary_id')
                ->constrained('beneficiaries')
                ->cascadeOnDelete();

            $table->foreignUuid('care_plan_id')
                ->nullable()
                ->constrained('care_plans')
                ->nullOnDelete();

            // Planned schedule
            $table->date('planned_date');
            $table->time('planned_start_time')->nullable();
            $table->time('planned_end_time')->nullable();

            // Actual execution (set on check-in / check-out)
            $table->timestamp('actual_start_at')->nullable();
            $table->timestamp('actual_end_at')->nullable();

            // GPS check-in coordinates (nullable — mobile only)
            $table->decimal('checkin_latitude', 10, 8)->nullable();
            $table->decimal('checkin_longitude', 11, 8)->nullable();

            $table->string('status')->default('planned');
            $table->string('visit_mode')->default('web');

            // Encrypted — may contain clinical observations
            $table->text('report_text')->nullable();
            $table->text('report_voice_transcript')->nullable();

            $table->string('cancellation_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['structure_id', 'planned_date']);
            $table->index(['structure_id', 'intervenant_id', 'planned_date']);
            $table->index(['structure_id', 'beneficiary_id']);
            $table->index(['structure_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interventions');
    }
};
