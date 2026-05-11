<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anonymous response to a QVCT campaign. Spec: CDC §M3 line "Anonymous
 * baromètre" — the row carries NO user_id, no IP, no device fingerprint.
 * Tenant scope is enforced via `structure_id` (already mandatory for
 * BelongsToStructure). The optional `team_tag` column lets the détecteur
 * aggregate per team without identifying who wrote what.
 *
 * Anti-correlation: the response intentionally has no FK back to the
 * person who submitted it. The mobile / web client tracks "I have
 * responded to campaign X" locally so the user can't double-submit.
 * Server-side dedup is impossible by design — that's the point.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qvct_responses', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('campaign_id')
                ->constrained('qvct_campaigns')
                ->cascadeOnDelete();

            // Per-question answers — { "morale": 4, "charge": 2, ... }.
            $table->jsonb('answers');

            // Optional team tag at submission time so the cartography can
            // aggregate without re-keying. NULL = no team disclosed.
            $table->string('team_tag')->nullable();

            // No user_id, no IP, no user-agent. By design.
            $table->timestamp('submitted_at')->useCurrent();

            $table->index(['structure_id', 'campaign_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qvct_responses');
    }
};
