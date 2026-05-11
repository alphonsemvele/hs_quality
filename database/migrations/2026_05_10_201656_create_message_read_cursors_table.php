<?php

declare(strict_types=1);

use App\Models\Message;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user, per-group read watermark. Phase 2 / M4.9 mark-read.
 *
 * Tracks the furthest message a user has read in each group.
 * "Unread" = messages in the group created after last_read_at.
 * Using a cursor rather than a per-message receipt table keeps the
 * write cost O(1) per mark-read call regardless of how many messages
 * are in the group.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_read_cursors', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(User::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('discussion_group_id')
                ->constrained('discussion_groups')
                ->cascadeOnDelete();

            // The furthest message this user has acknowledged reading.
            $table->foreignUuid('last_read_message_id')
                ->nullable()
                ->constrained('messages')
                ->nullOnDelete();

            $table->timestamp('last_read_at')->nullable();

            $table->timestamps();

            // One cursor per (user, group).
            $table->unique(['user_id', 'discussion_group_id']);
            $table->index(['structure_id', 'user_id', 'discussion_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_read_cursors');
    }
};
