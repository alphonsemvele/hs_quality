<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Q&A vote pivot. Spec: PHASE2_PROGRESS.md M4.12.
 *
 * One row per (user, answer). The unique constraint guarantees a
 * single vote per user per answer, making `QaService::vote()`
 * idempotent at the storage layer. The `upvotes` counter on
 * `qa_answers` is denormalised — the service keeps it in sync via
 * increment/decrement so the forum list doesn't need a sub-query.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qa_answer_votes', function (Blueprint $table): void {
            $table->id();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('qa_answer_id')
                ->constrained('qa_answers')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['qa_answer_id', 'user_id']);
            $table->index(['structure_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qa_answer_votes');
    }
};
