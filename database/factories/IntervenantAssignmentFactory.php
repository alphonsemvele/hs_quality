<?php

namespace Database\Factories;

use App\Models\Beneficiary;
use App\Models\IntervenantAssignment;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IntervenantAssignment>
 */
class IntervenantAssignmentFactory extends Factory
{
    protected $model = IntervenantAssignment::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'user_id' => User::factory()->intervenant(),
            'beneficiary_id' => Beneficiary::factory(),
            'assigned_by_user_id' => null,
            'assigned_at' => now(),
            'unassigned_at' => null,
            'notes' => null,
        ];
    }

    public function forStructure(Structure $structure): static
    {
        return $this->state(fn () => ['structure_id' => $structure->id]);
    }

    /**
     * Bind the assignment to concrete intervenant + beneficiary (both must
     * already exist in the same structure).
     */
    public function between(User $intervenant, Beneficiary $beneficiary): static
    {
        return $this->state(fn () => [
            'structure_id' => $intervenant->structure_id,
            'user_id' => $intervenant->id,
            'beneficiary_id' => $beneficiary->id,
        ]);
    }

    public function unassigned(?\DateTimeInterface $when = null): static
    {
        return $this->state(fn () => [
            'unassigned_at' => $when ?? now(),
        ]);
    }
}
