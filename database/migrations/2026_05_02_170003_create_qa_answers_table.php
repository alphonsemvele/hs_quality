<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qa_answers', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('qa_question_id')
                ->constrained('qa_questions')
                ->cascadeOnDelete();

            $table->foreignId('author_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->text('body');
            $table->unsignedSmallInteger('upvotes')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['structure_id', 'qa_question_id']);
        });

        // Now that qa_answers exists, add the back-reference FK on
        // qa_questions.accepted_answer_id (nullOnDelete to keep the
        // question alive if its accepted answer is deleted).
        Schema::table('qa_questions', function (Blueprint $table): void {
            $table->foreign('accepted_answer_id')
                ->references('id')->on('qa_answers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('qa_questions', function (Blueprint $table): void {
            $table->dropForeign(['accepted_answer_id']);
        });
        Schema::dropIfExists('qa_answers');
    }
};
