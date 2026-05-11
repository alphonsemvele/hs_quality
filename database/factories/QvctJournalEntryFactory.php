<?php

namespace Database\Factories;

use App\Enums\QvctMood;
use App\Models\QvctJournalEntry;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QvctJournalEntry>
 */
class QvctJournalEntryFactory extends Factory
{
    protected $model = QvctJournalEntry::class;

    public function definition(): array
    {
        $mood = fake()->randomElement(QvctMood::cases());

        return [
            'structure_id' => Structure::factory(),
            'user_id' => User::factory(),
            'body' => fake('fr_FR')->paragraph(),
            'mood' => $mood->value,
            'mood_score' => $mood->score(),
            'shared_with_rh' => false,
        ];
    }

    public function forUser(User $user): self
    {
        return $this->state([
            'structure_id' => $user->structure_id,
            'user_id' => $user->id,
        ]);
    }

    public function sharedWithRh(): self
    {
        return $this->state(['shared_with_rh' => true]);
    }
}
