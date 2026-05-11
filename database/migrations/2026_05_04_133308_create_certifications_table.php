<?php

use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Certifications — renewable competencies (BLS, gestes d'urgence,
 * habilitation électrique, manipulation des produits dangereux, ...).
 * Spec: PHASE2_PROGRESS.md M5.2.
 *
 * Distinct from `habilitations`: certifications expire on a fixed
 * cadence and require re-training. The expiry alert job (M5.9)
 * scans `expires_at` and fires reminders at T-90 / T-30 / T-7 days.
 *
 * `last_alerted_at` (nullable timestamp) is set by the alert job to
 * avoid double-firing for the same window. The alert windowing logic
 * is the unit-testable invariant in M5.18.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(User::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->string('type'); // BLS, gestes_urgence, habilitation_electrique, ...
            $table->string('reference_number')->nullable();
            $table->date('issued_on');
            $table->date('expires_at');
            $table->string('evidence_path')->nullable(); // S3

            $table->timestamp('last_alerted_at')->nullable(); // set by M5.9 job
            $table->string('last_alert_window')->nullable(); // 'T-90' | 'T-30' | 'T-7' | 'expired'

            $table->foreignId('recorded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['structure_id', 'user_id']);
            $table->index(['structure_id', 'expires_at']); // expiry sweep
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certifications');
    }
};
