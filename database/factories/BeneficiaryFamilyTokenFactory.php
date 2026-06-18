<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FamilyTokenScope;
use App\Models\Beneficiary;
use App\Models\BeneficiaryFamilyToken;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BeneficiaryFamilyToken>
 */
class BeneficiaryFamilyTokenFactory extends Factory
{
    protected $model = BeneficiaryFamilyToken::class;

    public function definition(): array
    {
        $structure = Structure::factory();

        return [
            'structure_id' => $structure,
            'beneficiary_id' => Beneficiary::factory()->forStructure($structure),
            'token_hash' => hash('sha256', Str::random(40)),
            'scope' => [FamilyTokenScope::ReadPlan->value, FamilyTokenScope::ReadInterventions->value],
            'issued_to_name' => fake()->name(),
            'issued_by_user_id' => function (array $attrs) {
                return User::factory()->state(['structure_id' => $attrs['structure_id']])->create()->id;
            },
            'expires_at' => now()->addDays(30),
            'last_used_at' => null,
        ];
    }

    public function expired(): self
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }

    public function withScope(FamilyTokenScope ...$scopes): self
    {
        return $this->state(['scope' => array_map(fn ($s) => $s->value, $scopes)]);
    }

    public function forBeneficiary(Beneficiary $beneficiary): self
    {
        return $this->state([
            'structure_id' => $beneficiary->structure_id,
            'beneficiary_id' => $beneficiary->id,
        ]);
    }
}
