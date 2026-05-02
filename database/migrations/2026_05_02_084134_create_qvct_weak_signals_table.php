<?php

use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Weak signal detected by WeakSignalDetector after a campaign closes.
 * Spec: CDC §M3 line "Weak-signal detection" + IMPLEMENTATION_PLAN line 437
 * "alerts to référent RH".
 *
 * One row per (campaign × team_tag × signal_type). When `team_tag` is
 * null, the signal is structure-wide (small structure with no team
 * granularity). Acknowledgement tracks which référent RH triaged the
 * signal and when.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qvct_weak_signals', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('campaign_id')
                ->constrained('qvct_campaigns')
                ->cascadeOnDelete();

            $table->string('team_tag')->nullable();
            $table->string('signal_type'); // QvctWeakSignalType enum

            // Detector context — score + threshold + sample size + free
            // human-readable summary line. JSONB so the detector can add
            // new diagnostics fields without a migration.
            $table->jsonb('details');

            // Severity 1-3 — purely a sort key for the dashboard. The
            // detector decides; not user-editable.
            $table->unsignedTinyInteger('severity')->default(1);

            $table->foreignId('acknowledged_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();

            $table->timestamps();

            $table->index(['structure_id', 'campaign_id', 'signal_type']);
            $table->index(['structure_id', 'acknowledged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qvct_weak_signals');
    }
};
