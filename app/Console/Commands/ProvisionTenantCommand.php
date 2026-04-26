<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\StructureTier;
use App\Enums\StructureType;
use App\Services\StructureService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * Provision a new tenant: structure + initial dirigeant.
 *
 * Operator workflow:
 *   php artisan tenant:provision \
 *       --code=SAAD-PARIS13 \
 *       --name="SAAD Paris 13e" \
 *       --type=saad \
 *       --tier=essential \
 *       --siret=12345678901234 \
 *       --dirigeant-first-name="Marie" \
 *       --dirigeant-last-name="Durand" \
 *       --dirigeant-email="m.durand@example.fr"
 *
 * Or run without flags to be prompted for each field:
 *   php artisan tenant:provision
 *
 * Outputs the password-reset URL to hand off to the dirigeant. They reset
 * via the standard Fortify flow before first login (registration is
 * disabled — see config/fortify.php).
 */
class ProvisionTenantCommand extends Command
{
    protected $signature = 'tenant:provision
        {--code= : Unique structure code (UPPERCASE, max 50 chars)}
        {--name= : Structure display name}
        {--type= : saad|ssiad|spasad|esad|mandataire|ccas}
        {--tier=essential : essential|pro|premium}
        {--address= : Street address (optional)}
        {--siret= : SIRET — exactly 14 digits (optional)}
        {--dirigeant-first-name= : First name of initial dirigeant}
        {--dirigeant-last-name= : Last name of initial dirigeant}
        {--dirigeant-email= : Email of initial dirigeant}
        {--dirigeant-phone= : Phone of initial dirigeant (optional)}';

    protected $description = 'Provision a new tenant structure with its initial dirigeant.';

    public function handle(StructureService $service): int
    {
        $structureData = $this->collectStructureData();
        $dirigeantData = $this->collectDirigeantData();

        $payload = ['structure' => $structureData, 'dirigeant' => $dirigeantData];

        $errors = $this->validate($payload);
        if (! empty($errors)) {
            $this->components->error('Provision aborted — invalid input:');
            foreach ($errors as $field => $msgs) {
                foreach ($msgs as $msg) {
                    $this->components->bulletList(["{$field}: {$msg}"]);
                }
            }

            return self::INVALID;
        }

        $result = $service->provision($structureData, $dirigeantData);

        $this->components->info(sprintf(
            'Provisioned structure "%s" (%s) — id=%s',
            $result['structure']->name,
            $result['structure']->code,
            $result['structure']->id,
        ));

        $this->components->twoColumnDetail('Dirigeant', $result['dirigeant']->email);
        $this->components->twoColumnDetail('Type', $result['structure']->type->label());
        $this->components->twoColumnDetail('Tier', $result['structure']->tier->label());

        if ($result['password_reset_url'] !== null) {
            $this->newLine();
            $this->components->info('Password reset URL (give to dirigeant):');
            $this->line('  '.$result['password_reset_url']);
        } else {
            $this->components->warn('Password-reset URL could not be generated — check mail config.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array{code: string, name: string, type: string, tier: string, address: ?string, siret: ?string}
     */
    private function collectStructureData(): array
    {
        return [
            'code' => $this->stringOrPrompt('code', 'Structure code (UPPERCASE)'),
            'name' => $this->stringOrPrompt('name', 'Structure name'),
            'type' => $this->enumOrPrompt('type', 'Structure type', StructureType::cases()),
            'tier' => $this->enumOrPrompt(
                'tier', 'Tier', StructureTier::cases(), default: StructureTier::Essential->value,
            ),
            'address' => $this->option('address'),
            'siret' => $this->option('siret'),
        ];
    }

    /**
     * @return array{first_name: string, last_name: string, email: string, phone: ?string}
     */
    private function collectDirigeantData(): array
    {
        return [
            'first_name' => $this->stringOrPrompt('dirigeant-first-name', 'Dirigeant first name'),
            'last_name' => $this->stringOrPrompt('dirigeant-last-name', 'Dirigeant last name'),
            'email' => $this->stringOrPrompt('dirigeant-email', 'Dirigeant email'),
            'phone' => $this->option('dirigeant-phone'),
        ];
    }

    private function stringOrPrompt(string $option, string $label): string
    {
        $value = $this->option($option);
        if ($value !== null && $value !== '') {
            return (string) $value;
        }

        if (! $this->input->isInteractive()) {
            $this->fail("Missing required option --{$option}");
        }

        return text($label, required: true);
    }

    /**
     * @param  list<\BackedEnum>  $cases
     */
    private function enumOrPrompt(string $option, string $label, array $cases, ?string $default = null): string
    {
        $value = $this->option($option) ?? $default;
        $valid = array_map(fn (\BackedEnum $c) => $c->value, $cases);

        if ($value !== null && in_array($value, $valid, true)) {
            return (string) $value;
        }

        if (! $this->input->isInteractive()) {
            $this->fail("Missing or invalid --{$option}; expected one of: ".implode(', ', $valid));
        }

        $options = collect($cases)->mapWithKeys(fn (\BackedEnum $c) => [$c->value => $c->value])->all();

        return select($label, $options, default: $default ?? array_key_first($options));
    }

    /**
     * @param  array{structure: array<string, mixed>, dirigeant: array<string, mixed>}  $payload
     * @return array<string, list<string>>
     */
    private function validate(array $payload): array
    {
        $rules = [
            'structure.code' => ['required', 'string', 'max:50', Rule::unique('structures', 'code')],
            'structure.name' => ['required', 'string', 'max:255'],
            'structure.type' => ['required', Rule::enum(StructureType::class)],
            'structure.tier' => ['required', Rule::enum(StructureTier::class)],
            'structure.address' => ['nullable', 'string', 'max:1000'],
            'structure.siret' => ['nullable', 'string', 'size:14', 'regex:/^\d{14}$/'],

            'dirigeant.first_name' => ['required', 'string', 'max:100'],
            'dirigeant.last_name' => ['required', 'string', 'max:100'],
            'dirigeant.email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'dirigeant.phone' => ['nullable', 'string', 'max:50'],
        ];

        return Validator::make($payload, $rules)->errors()->messages();
    }
}
