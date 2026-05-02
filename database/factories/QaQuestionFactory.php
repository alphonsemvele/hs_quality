<?php

namespace Database\Factories;

use App\Models\QaQuestion;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QaQuestion>
 */
class QaQuestionFactory extends Factory
{
    protected $model = QaQuestion::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'author_id' => User::factory(),
            'title' => fake('fr_FR')->sentence(),
            'body' => fake('fr_FR')->paragraph(),
            'accepted_answer_id' => null,
        ];
    }

    public function forStructure(Structure $structure): self
    {
        return $this->state(['structure_id' => $structure->id]);
    }
}
