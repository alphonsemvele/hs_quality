<?php

namespace Database\Factories;

use App\Models\QaAnswer;
use App\Models\QaAnswerVote;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QaAnswerVote>
 */
class QaAnswerVoteFactory extends Factory
{
    protected $model = QaAnswerVote::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'qa_answer_id' => QaAnswer::factory(),
            'user_id' => User::factory(),
        ];
    }

    public function forAnswer(QaAnswer $answer): self
    {
        return $this->state([
            'structure_id' => $answer->structure_id,
            'qa_answer_id' => $answer->id,
        ]);
    }
}
