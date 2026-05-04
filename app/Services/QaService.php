<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\QaAnswer;
use App\Models\QaQuestion;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * QaService — forum Q&A operations: ask, answer, accept-answer, vote.
 * Spec: PHASE2_PROGRESS.md M4.12.
 *
 * accept-answer is reserved to the question's author and cross-checks
 * that the candidate answer belongs to the same question (otherwise a
 * malicious payload could "accept" an answer from a different thread).
 *
 * vote is idempotent at the storage layer via the unique constraint on
 * (qa_answer_id, user_id) on qa_answer_votes; the denormalised
 * `upvotes` counter is updated atomically inside the same transaction
 * so the forum list stays consistent.
 */
class QaService
{
    public function ask(Structure $structure, User $author, string $title, string $body): QaQuestion
    {
        return QaQuestion::create([
            'structure_id' => $structure->id,
            'author_id' => $author->id,
            'title' => $title,
            'body' => $body,
        ]);
    }

    public function answer(QaQuestion $question, User $author, string $body): QaAnswer
    {
        return QaAnswer::create([
            'structure_id' => $question->structure_id,
            'qa_question_id' => $question->id,
            'author_id' => $author->id,
            'body' => $body,
            'upvotes' => 0,
        ]);
    }

    public function acceptAnswer(QaQuestion $question, QaAnswer $answer, User $accepter): QaQuestion
    {
        if ($question->author_id !== $accepter->id) {
            throw new HttpException(403, 'Seul l’auteur de la question peut accepter une réponse.');
        }

        if ($answer->qa_question_id !== $question->id) {
            throw new HttpException(422, 'Cette réponse n’appartient pas à la question.');
        }

        $question->update(['accepted_answer_id' => $answer->id]);

        return $question->fresh();
    }

    /**
     * Toggle a user's upvote on an answer. Idempotent: calling twice
     * returns the answer to its pre-vote state. Returns the fresh
     * answer with the up-to-date counter.
     */
    public function vote(QaAnswer $answer, User $voter): QaAnswer
    {
        return DB::transaction(function () use ($answer, $voter): QaAnswer {
            $existing = DB::table('qa_answer_votes')
                ->where('qa_answer_id', $answer->id)
                ->where('user_id', $voter->id)
                ->first();

            if ($existing !== null) {
                DB::table('qa_answer_votes')
                    ->where('id', $existing->id)
                    ->delete();
                $answer->decrement('upvotes');
            } else {
                DB::table('qa_answer_votes')->insert([
                    'structure_id' => $answer->structure_id,
                    'qa_answer_id' => $answer->id,
                    'user_id' => $voter->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $answer->increment('upvotes');
            }

            return $answer->fresh();
        });
    }
}
