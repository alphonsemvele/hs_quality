<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot — group ↔ user with a per-membership role. Spec:
 * PHASE2_PROGRESS.md M4.2.
 *
 * `role` enum: 'member' | 'admin'. Admin can add/remove members and
 * archive the group; both can post. Unique (group_id, user_id) so a
 * user cannot be added twice to the same group.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discussion_group_members', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('discussion_group_id')
                ->constrained('discussion_groups')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('role')->default('member');

            $table->timestamps();

            $table->unique(['discussion_group_id', 'user_id'], 'discussion_group_member_unique');
            $table->index(['structure_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discussion_group_members');
    }
};
