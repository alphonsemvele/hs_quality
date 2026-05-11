<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StructureStatus;
use App\Enums\StructureTier;
use App\Enums\StructureType;
use App\Enums\UserType;
use App\Models\Structure;
use App\Models\User;
use App\Notifications\StructureWelcomeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Provisioning + lifecycle for Structure (the tenant root).
 *
 * Used by:
 *   - `php artisan tenant:provision` (operator CLI onboarding)
 *   - StructureSeeder (dev demo data)
 *   - Future Admin/StructureController (operator self-service UI)
 *
 * Provisioning is atomic: creating a Structure WITHOUT an initial dirigeant
 * leaves the tenant unreachable (no one can log in to it). Both rows are
 * inserted in one transaction; the dirigeant role is assigned with the
 * new structure's team_id.
 */
class StructureService
{
    /**
     * @param  array{
     *   code: string,
     *   name: string,
     *   type: StructureType|string,
     *   tier?: StructureTier|string|null,
     *   address?: string|null,
     *   siret?: string|null,
     * }  $structureData
     * @param  array{
     *   first_name: string,
     *   last_name: string,
     *   email: string,
     *   phone?: string|null,
     * }  $dirigeantData
     * @return array{structure: Structure, dirigeant: User, password_reset_url: string|null}
     */
    public function provision(array $structureData, array $dirigeantData): array
    {
        $result = DB::transaction(function () use ($structureData, $dirigeantData): array {
            $structure = Structure::create([
                'code' => mb_strtoupper($structureData['code']),
                'name' => $structureData['name'],
                'type' => $this->castEnum($structureData['type'], StructureType::class),
                'address' => $structureData['address'] ?? null,
                'siret' => $structureData['siret'] ?? null,
                'tier' => $this->castEnum(
                    $structureData['tier'] ?? StructureTier::Essential,
                    StructureTier::class,
                ),
                'status' => StructureStatus::Active->value,
            ]);

            $dirigeant = User::create([
                'structure_id' => $structure->id,
                'first_name' => $dirigeantData['first_name'],
                'last_name' => $dirigeantData['last_name'],
                'email' => mb_strtolower($dirigeantData['email']),
                // Random throwaway password — the dirigeant resets via the
                // emailed link before they ever sign in.
                'password' => Hash::make(Str::random(40)),
                'phone' => $dirigeantData['phone'] ?? null,
                'employee_number' => mb_strtoupper(Str::random(8)),
                'type' => UserType::Dirigeant->value,
                'status' => 'active',
                'email_verified_at' => null,
            ]);

            // Assign the dirigeant role *with* the new structure's team_id —
            // the standard tenant-scoped role assignment pattern.
            app(PermissionRegistrar::class)
                ->setPermissionsTeamId($structure->id);
            $dirigeant->assignRole('dirigeant');

            $resetUrl = $this->generatePasswordResetUrl($dirigeant);

            return [
                'structure' => $structure->fresh(),
                'dirigeant' => $dirigeant->fresh(),
                'password_reset_url' => $resetUrl,
            ];
        });

        // Dispatch welcome email outside the transaction so the queued job
        // runs against already-committed rows. If the queue worker is down
        // the notification retries automatically; provisioning is unaffected.
        $result['dirigeant']->notify(
            new StructureWelcomeNotification($result['structure'], $result['password_reset_url'])
        );

        return $result;
    }

    public function suspend(Structure $structure): Structure
    {
        $structure->update(['status' => StructureStatus::Suspended->value]);

        return $structure->fresh();
    }

    public function reactivate(Structure $structure): Structure
    {
        $structure->update(['status' => StructureStatus::Active->value]);

        return $structure->fresh();
    }

    public function changeTier(Structure $structure, StructureTier $tier): Structure
    {
        $structure->update(['tier' => $tier->value]);

        return $structure->fresh();
    }

    /**
     * Issue a password-reset token and return the signed reset URL. Operator
     * pastes it into the dirigeant's onboarding email (or hands it over IRL).
     * Returns null if the mail/notification stack isn't configured (smoke
     * tests should not depend on outbound mail).
     */
    private function generatePasswordResetUrl(User $user): ?string
    {
        try {
            $token = Password::broker()->createToken($user);

            return route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ]);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @template T of \BackedEnum
     *
     * @param  T|string  $value
     * @param  class-string<T>  $enum
     */
    private function castEnum(\BackedEnum|string $value, string $enum): string
    {
        return $value instanceof \BackedEnum ? $value->value : $enum::from($value)->value;
    }
}
