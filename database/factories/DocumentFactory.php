<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'title' => fake()->randomElement([
                'Procédure soins de nursing',
                'Fiche pratique chute bénéficiaire',
                'Formulaire déclaration d\'incident',
                'Protocole hygiène mains',
            ]),
            'description' => fake('fr_FR')->sentence(),
            'disk' => 's3',
            'path' => 'documents/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(10_000, 5_000_000),
            'version' => 1,
            'roles_acl' => null,
            'uploaded_by' => User::factory(),
        ];
    }

    public function forStructure(Structure $structure): self
    {
        return $this->state(['structure_id' => $structure->id]);
    }

    public function visibleToRoles(array $roles): self
    {
        return $this->state(['roles_acl' => $roles]);
    }
}
