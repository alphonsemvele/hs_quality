<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campaign — instantiation of a questionnaire over a launch window.
 * Spec: CDC §M3. Multiple campaigns may exist per questionnaire (one per
 * cadence period). Responses are recorded against a campaign, not the
 * template — versioning a template after it's already been campaigned
 * never silently rewrites historical aggregates.
 *
 * `target_team` is nullable (structure-wide campaign by default). When
 * set, only intervenants of that team can submit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qvct_campaigns', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('questionnaire_id')
                ->constrained('qvct_questionnaires')
                ->cascadeOnDelete();

            $table->string('title');
            $table->date('opens_at');
            $table->date('closes_at');

            // QvctCampaignStatus enum — string for forward-compat.
            $table->string('status')->default('draft');

            // Optional targeting — null = structure-wide. String tag for
            // now (Phase 2 has no formal `teams` table yet); will become
            // FK in Phase 3 if/when teams are first-class.
            $table->string('target_team')->nullable();

            $table->foreignId('launched_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('closed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['structure_id', 'status']);
            $table->index(['structure_id', 'opens_at', 'closes_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qvct_campaigns');
    }
};
