<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Periodic QVCT-indicator snapshot. Spec: PHASE2_PROGRESS.md M3.26 +
 * CDC §M3 line "QVCT indicator tracking: absenteeism, turnover,
 * work accidents, baromètre satisfaction".
 *
 * One row per (structure × period_start). period_start is normalised
 * to the first day of the month so a unique constraint guarantees
 * exactly one snapshot per tenant per month — re-running the
 * IndicatorIngestionService updates the same row in place.
 *
 * Auto-computable columns today:
 *   barometer_mean_score  — average over closed campaigns' responses
 *
 * Manual-entry columns (RH fills these via the API):
 *   absenteeism_rate, turnover_rate, work_accidents_count
 *
 * Nullable across the board so a tenant can record what they have
 * and leave the rest blank.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qvct_indicators', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->date('period_start');
            $table->date('period_end');

            $table->decimal('absenteeism_rate', 5, 2)->nullable();
            $table->decimal('turnover_rate', 5, 2)->nullable();
            $table->unsignedInteger('work_accidents_count')->nullable();
            $table->decimal('barometer_mean_score', 4, 2)->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('captured_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('captured_at')->nullable();

            $table->timestamps();

            $table->unique(['structure_id', 'period_start']);
            $table->index(['structure_id', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qvct_indicators');
    }
};
