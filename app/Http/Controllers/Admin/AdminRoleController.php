<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminRoleController extends Controller
{
    public function index(): Response
    {
        $roles = Role::withCount('users')
            ->with('permissions')
            ->latest()
            ->get();

        $stats = [
            'total'              => $roles->count(),
            'avec_utilisateurs'  => $roles->where('users_count', '>', 0)->count(),
            'total_permissions'  => Permission::count(),
        ];

        return Inertia::render('admin/role', [
            'roles'       => $roles,
            'permissions' => Permission::orderBy('nom')->get(),
            'stats'       => $stats,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nom'           => ['required', 'string', 'max:50', 'unique:roles,nom'],
            'description'   => ['nullable', 'string', 'max:255'],
            'couleur'       => ['nullable', 'string', 'max:7'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role = Role::create([
            'nom'         => strtolower(trim($data['nom'])),
            'description' => $data['description'] ?? null,
            'couleur'     => $data['couleur'] ?? '#8B5CF6',
        ]);

        if (!empty($data['permissions'])) {
            $role->permissions()->sync($data['permissions']);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role cree avec succes.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'nom'           => ['required', 'string', 'max:50', 'unique:roles,nom,' . $role->id],
            'description'   => ['nullable', 'string', 'max:255'],
            'couleur'       => ['nullable', 'string', 'max:7'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role->update([
            'nom'         => strtolower(trim($data['nom'])),
            'description' => $data['description'] ?? null,
            'couleur'     => $data['couleur'] ?? $role->couleur,
        ]);

        $role->permissions()->sync($data['permissions'] ?? []);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role mis a jour avec succes.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->users()->count() > 0) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'Ce role est attribue a des utilisateurs. Veuillez les reassigner.');
        }

        $role->permissions()->detach();
        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role supprime.');
    }
}