<?php

namespace Database\Factories;

use App\Models\QaAnswer;
use App\Models\QaQuestion;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QaAnswer>
 */
class QaAnswerFactory extends Factory
{
    protected $model = QaAnswer::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'qa_question_id' => QaQuestion::factory(),
            'author_id' => User::factory(),
            'body' => fake('fr_FR')->paragraph(),
            'upvotes' => 0,
        ];
    }

    public function forQuestion(QaQuestion $question): self
    {
        return $this->state([
            'structure_id' => $question->structure_id,
            'qa_question_id' => $question->id,
        ]);
    }
}
