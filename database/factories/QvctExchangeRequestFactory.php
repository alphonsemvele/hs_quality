<?php

namespace Database\Factories;

use App\Enums\QvctExchangeAddresseeRole;
use App\Enums\QvctExchangeStatus;
use App\Models\QvctExchangeRequest;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QvctExchangeRequest>
 */
class QvctExchangeRequestFactory extends Factory
{
    protected $model = QvctExchangeRequest::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'requester_id' => User::factory(),
            'addressee_role' => QvctExchangeAddresseeRole::Rh->value,
            'status' => QvctExchangeStatus::Pending->value,
            'message' => fake('fr_FR')->paragraph(),
            'accepted_by' => null,
            'accepted_at' => null,
            'scheduled_at' => null,
            'closed_at' => null,
            'closed_reason' => null,
        ];
    }

    public function fromUser(User $requester): self
    {
        return $this->state([
            'structure_id' => $requester->structure_id,
            'requester_id' => $requester->id,
        ]);
    }

    public function toRh(): self
    {
        return $this->state(['addressee_role' => QvctExchangeAddresseeRole::Rh->value]);
    }

    public function toManager(): self
    {
        return $this->state(['addressee_role' => QvctExchangeAddresseeRole::Manager->value]);
    }
}
