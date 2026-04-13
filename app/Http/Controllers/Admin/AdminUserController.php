<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    const STATUT_ACTIF    = 'actif';
    const STATUT_CONGE    = 'conge';
    const STATUT_MISSION  = 'mission';
    const STATUT_INACTIF  = 'inactif';

    const FONCTIONS = [
        'Médecin',
        'Infirmière',
        'Infirmière Chef',
        'Sage-femme',
        'Pharmacien(ne)',
        'Technicien(ne)',
        'Administratif',
        'Admin',
    ];

    const ROLES = [
        'admin',
        'medecin',
        'infirmier',
        'pharmacien',
        'laborantin',
        'receptionniste',
        'comptable',
    ];

    public static function getStatuts(): array
    {
        return [
            self::STATUT_ACTIF,
            self::STATUT_CONGE,
            self::STATUT_MISSION,
            self::STATUT_INACTIF,
        ];
    }

    /**
     * Liste paginée avec filtres + stats.
     */
    public function index(Request $request): Response
    {
        $query = User::with('service');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name',       'like', "%{$search}%")
                  ->orWhere('lastname',  'like', "%{$search}%")
                  ->orWhere('matricule', 'like', "%{$search}%")
                  ->orWhere('email',     'like', "%{$search}%")
                  ->orWhere('specialite','like', "%{$search}%");
            });
        }

        if ($request->filled('fonction')) {
            $query->where('fonction', $request->fonction);
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        $stats = [
            'total'     => User::count(),
            'actifs'    => User::where('statut', self::STATUT_ACTIF)->count(),
            'inactifs'  => User::where('statut', self::STATUT_INACTIF)->count(),
            'suspendus' => User::where('statut', self::STATUT_CONGE)->count(),   // adapter si besoin
            'medecins'  => User::where('fonction', 'Médecin')->count(),
        ];

        $users    = $query->latest()->paginate(15)->withQueryString();
        $services = Service::where('actif', true)->get();

        return Inertia::render('admin/users', [
            'users'    => $users,
            'stats'    => $stats,
            'filters'  => $request->only(['search', 'fonction', 'service_id', 'statut']),
            'statuts'  => self::getStatuts(),
            'fonctions'=> self::FONCTIONS,
            'roles'    => self::ROLES,
            'services' => $services,
        ]);
    }

    /**
     * Créer un utilisateur (admin peut créer n'importe quel rôle).
     */
    public function store(UserStoreRequest $request): RedirectResponse
    {
        $prefix = match ($request->fonction) {
            'Médecin'                       => 'MED',
            'Infirmière', 'Infirmière Chef' => 'INF',
            'Sage-femme'                    => 'SF',
            'Pharmacien(ne)'                => 'PHAR',
            'Technicien(ne)'                => 'TECH',
            default                         => 'ADM',
        };

        $lastUser  = User::where('matricule', 'like', $prefix . '-%')->orderBy('id', 'desc')->first();
        $number    = $lastUser ? intval(substr($lastUser->matricule, -3)) + 1 : 1;
        $matricule = $prefix . '-' . str_pad($number, 3, '0', STR_PAD_LEFT);

        $data = $request->validated();

        User::create([
            ...$data,
            'matricule' => $matricule,
            'password'  => Hash::make($data['password'] ?? 'password123'),
            'statut'    => $data['statut'] ?? self::STATUT_ACTIF,
        ]);

        return redirect()->route('admin.utilisateurs.index')
            ->with('success', 'Utilisateur créé avec succès.');
    }

    /**
     * Mettre à jour un utilisateur.
     */
    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.utilisateurs.index')
            ->with('success', 'Utilisateur mis à jour avec succès.');
    }

    /**
     * Changer uniquement le statut (PATCH).
     */
    public function updateStatut(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'statut' => ['required', 'in:' . implode(',', self::getStatuts())],
        ]);

        $user->update(['statut' => $request->statut]);

        return redirect()->route('admin.utilisateurs.index')
            ->with('success', 'Statut mis à jour avec succès.');
    }

    /**
     * Supprimer définitivement (admin uniquement).
     */
    public function destroy(User $user): RedirectResponse
    {
        // Empêcher la suppression de son propre compte
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.utilisateurs.index')
                ->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $user->delete();

        return redirect()->route('admin.utilisateurs.index')
            ->with('success', 'Utilisateur supprimé définitivement.');
    }
}