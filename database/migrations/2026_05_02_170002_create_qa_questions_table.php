<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Q&A forum question. Spec: PHASE2_PROGRESS.md M4.6 + CDC §M4
 * "Q&A forum moderated by référents".
 *
 * `accepted_answer_id` is a self-reference resolved after the answers
 * row is created — added as a separate constrained column with a
 * non-cascading nullOnDelete so a deleted answer doesn't take the
 * question with it. The actual FK is added in the qa_answers
 * migration to avoid a circular FK dependency at create-table time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qa_questions', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('author_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('body');

            // Set after an answer is accepted; null FK constraint added
            // in the next migration after qa_answers exists.
            $table->uuid('accepted_answer_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['structure_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qa_questions');
    }
};
