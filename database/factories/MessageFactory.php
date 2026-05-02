<?php

namespace Database\Factories;

use App\Models\DiscussionGroup;
use App\Models\Message;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'discussion_group_id' => DiscussionGroup::factory(),
            'author_id' => User::factory(),
            'body' => fake('fr_FR')->sentence(),
            'attachments' => null,
            'edited_at' => null,
        ];
    }

    public function inGroup(DiscussionGroup $group, ?User $author = null): self
    {
        return $this->state([
            'structure_id' => $group->structure_id,
            'discussion_group_id' => $group->id,
            'author_id' => $author?->id ?? User::factory()->forStructure($group->structure)->create()->id,
        ]);
    }
}
