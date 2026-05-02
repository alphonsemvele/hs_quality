<?php

namespace Database\Factories;

use App\Models\NewsFeedPost;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsFeedPost>
 */
class NewsFeedPostFactory extends Factory
{
    protected $model = NewsFeedPost::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'title' => fake('fr_FR')->sentence(6),
            'body' => fake('fr_FR')->paragraphs(2, true),
            'author_id' => User::factory(),
            'pinned' => false,
            'archived_at' => null,
        ];
    }

    public function forStructure(Structure $structure): self
    {
        return $this->state(['structure_id' => $structure->id]);
    }

    public function pinned(): self
    {
        return $this->state(['pinned' => true]);
    }
}
