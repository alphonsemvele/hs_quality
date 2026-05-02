<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Message inside a discussion group. Spec: PHASE2_PROGRESS.md M4.3.
 *
 * `body` is encrypted at rest because intervenants may discuss
 * sensitive intervention context — same RGPD Art 9 reasoning as
 * intervention.report_text and qvct_journal_entries.body.
 *
 * `attachments` is a JSON array of {name, url, mime_type, size_bytes}
 * objects pointing at S3 SSE-KMS storage. Soft-delete keeps a
 * tombstone so audit trail isn't broken when a moderator removes a
 * message; the body is wiped on delete via the model's deleting hook.
 *
 * `edited_at` enables the 5-minute edit window from CDC §M4 — UI
 * disables edit if older than that. Server-side enforcement lives in
 * the service layer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('discussion_group_id')
                ->constrained('discussion_groups')
                ->cascadeOnDelete();

            $table->foreignId('author_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->text('body');
            $table->jsonb('attachments')->nullable();

            $table->timestamp('edited_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['structure_id', 'discussion_group_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
