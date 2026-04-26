<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserType;
use App\Models\Structure;
use App\Models\User;
use App\Notifications\UserInvitedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * In-tenant user provisioning. Used by the dirigeant / coordinateur to
 * onboard team members (intervenants, coordinateurs, RH, référent qualité).
 *
 * Flow per CDC §3:
 *   1. Admin POSTs name + email + role.
 *   2. Service creates a User row scoped to the admin's structure with a
 *      throwaway password and email_verified_at = null.
 *   3. Service assigns the role at the structure's team_id (Spatie).
 *   4. Service generates a password-reset token and sends an invitation
 *      email containing the reset link.
 *   5. Recipient clicks the link, sets a real password, can log in.
 *
 * Public registration is intentionally OFF (Wave 0 / C2). All accounts
 * land here, never through self-service.
 */
class UserInvitationService
{
    /**
     * @param  array{first_name: string, last_name: string, email: string, type: UserType|string, phone?: string|null, employee_number?: string|null}  $data
     * @return array{user: User, invitation_url: string|null}
     */
    public function invite(array $data, Structure $structure, User $invitedBy): array
    {
        $type = $data['type'] instanceof UserType
            ? $data['type']
            : UserType::from($data['type']);

        return DB::transaction(function () use ($data, $structure, $type, $invitedBy): array {
            $user = User::create([
                'structure_id' => $structure->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => mb_strtolower($data['email']),
                // Throwaway random password — overwritten when the invitee
                // sets their real password via the reset link.
                'password' => Hash::make(Str::random(40)),
                'phone' => $data['phone'] ?? null,
                'employee_number' => $data['employee_number'] ?? mb_strtoupper(Str::random(8)),
                'type' => $type->value,
                'status' => 'active',
                'email_verified_at' => null,
            ]);

            // Tenant-scoped role assignment.
            app(PermissionRegistrar::class)->setPermissionsTeamId($structure->id);
            $user->assignRole($type->value);

            $invitationUrl = $this->generateInvitationUrl($user);

            // Try to send the invite email; never fail provisioning because
            // mail config is misconfigured. The invitation_url is also
            // returned so the inviter can hand off the link directly.
            try {
                $user->notify(new UserInvitedNotification($structure, $invitedBy, $invitationUrl));
            } catch (\Throwable $e) {
                Log::warning('user.invite mail dispatch failed', [
                    'user_id' => $user->id,
                    'invited_by' => $invitedBy->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return [
                'user' => $user->fresh(),
                'invitation_url' => $invitationUrl,
            ];
        });
    }

    public function deactivate(User $user): User
    {
        $user->update(['status' => 'inactive']);
        // Revoke all Sanctum tokens so any cached mobile session dies.
        $user->tokens()->delete();

        return $user->fresh();
    }

    public function reactivate(User $user): User
    {
        $user->update(['status' => 'active']);

        return $user->fresh();
    }

    private function generateInvitationUrl(User $user): ?string
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
}
