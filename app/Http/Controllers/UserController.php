<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserType;
use App\Http\Requests\Users\InviteUserRequest;
use App\Models\User;
use App\Services\UserInvitationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * In-tenant user management surface (admin within a structure invites
 * + manages their team).
 *
 * Sister controller to Admin/StructureController:
 *   - Admin/StructureController = platform operator → manages tenants
 *   - UserController            = tenant admin     → manages users in
 *                                                    THEIR tenant
 *
 * All endpoints are inside the `tenant` middleware group, so the global
 * BelongsToStructure scope hides foreign-tenant users from queries.
 */
class UserController extends Controller
{
    public function __construct(
        private readonly UserInvitationService $service,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        // User does not carry the BelongsToStructure global scope (it is the
        // auth subject the tenant resolver reads), so the structure filter is
        // applied explicitly here. Platform admins belong to no tenant — they
        // must never surface in a structure's directory (cross-tenant leak)
        // and clicking their row would 403 in UserPolicy::before anyway.
        $users = User::query()
            ->where('structure_id', currentStructure()->id)
            ->where('is_platform_admin', false)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(25)
            ->through(fn (User $u) => $this->summary($u));

        return Inertia::render('dashboard/users/index', [
            'users' => $users,
            'roles' => $this->roleOptions(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('dashboard/users/create', [
            'roles' => $this->roleOptions(),
        ]);
    }

    public function store(InviteUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Role-specific gate AFTER validation so a legitimate inviter sees
        // field errors when both shape and role are wrong, but a request
        // for a forbidden role (portal, super_admin) is still rejected.
        $this->authorize('invite', [User::class, $data['type']]);

        $result = $this->service->invite(
            data: $data,
            structure: currentStructure(),
            invitedBy: $request->user(),
        );

        return redirect()->route('users.show', $result['user'])
            ->with('success', sprintf(
                'Invitation envoyée à %s.',
                $result['user']->email,
            ))
            ->with('invitation_url', $result['invitation_url']);
    }

    public function show(User $user): Response
    {
        $this->authorize('view', $user);

        return Inertia::render('dashboard/users/show', [
            'user' => $this->detail($user),
        ]);
    }

    public function deactivate(User $user): RedirectResponse
    {
        $this->authorize('deactivate', $user);

        $this->service->deactivate($user);

        return back()->with('success', 'Utilisateur désactivé.');
    }

    public function reactivate(User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $this->service->reactivate($user);

        return back()->with('success', 'Utilisateur réactivé.');
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(User $user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'name' => trim($user->first_name.' '.$user->last_name),
            'email' => $user->email,
            'type' => $user->type instanceof UserType ? $user->type->value : (string) $user->type,
            'type_label' => $user->type instanceof UserType ? $user->type->label() : '',
            'status' => $user->status,
            'has_mfa_enrolled' => $user->two_factor_confirmed_at !== null,
            'requires_mfa' => $user->requiresMandatoryMfa(),
            'pending_invite' => $user->email_verified_at === null,
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(User $user): array
    {
        return [
            ...$this->summary($user),
            'phone' => $user->phone,
            'employee_number' => $user->employee_number,
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function roleOptions(): array
    {
        // Role dropdown matches the 5 invitable tenant personas (the portal
        // role isn't invitable from this admin surface).
        return collect([
            UserType::Intervenant,
            UserType::Coordinateur,
            UserType::Dirigeant,
            UserType::ReferentQualite,
            UserType::Rh,
        ])->map(fn (UserType $t) => ['value' => $t->value, 'label' => $t->label()])->all();
    }
}
