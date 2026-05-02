<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Personal QVCT journal entry — individual mood + free-text note kept
 * by an intervenant. Spec: PHASE2_PROGRESS.md M3.22 + CDC §M3 line
 * "Optional emotional journal".
 *
 * Per-user (NOT anonymous, unlike qvct_responses) — the user IS the
 * subject. Privacy is enforced by:
 *   1. user_id FK + a Policy that returns false unless owner OR
 *      (RH AND shared_with_rh = true)
 *   2. body column is encrypted at rest (RGPD Art 9 health data —
 *      mental-health expressions explicitly named)
 *   3. soft-delete only — RGPD erasure deletes via the dedicated
 *      service so audit traces remain
 *
 * The shared_with_rh flag is the explicit consent gate — without it the
 * referent RH cannot read the entry. The aggregate mood-trend in the
 * dashboard reads the score column (NOT body), which doesn't require
 * decryption nor disclose narrative content.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qvct_journal_entries', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Encrypted free-text — mood narrative.
            $table->text('body');

            // QvctMood enum (string for forward-compat).
            $table->string('mood');

            // Numeric mood score 1-5 — derivable from `mood` but stored
            // for cheap aggregate queries (no decryption needed for
            // dashboard mood-trend tile).
            $table->unsignedTinyInteger('mood_score');

            // Explicit consent gate. Default false — RH never sees an
            // entry the user didn't choose to share.
            $table->boolean('shared_with_rh')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['structure_id', 'user_id', 'created_at']);
            $table->index(['structure_id', 'shared_with_rh', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qvct_journal_entries');
    }
};
