<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    const STATUT_EN_SERVICE = 'actif';
    const STATUT_EN_CONGE = 'conge';
    const STATUT_EN_MISSION = 'mission';
    const STATUT_INACTIF = 'inactif';

    const FONCTIONS = [
        'Médecin',
        'Infirmière',
        'Infirmière Chef',
        'Sage-femme',
        'Pharmacien(ne)',
        'Technicien(ne)',
        'Administratif',
        'Admin'
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
            self::STATUT_EN_SERVICE,
            self::STATUT_EN_CONGE,
            self::STATUT_EN_MISSION,
            self::STATUT_INACTIF,
        ];
    }

    public function index(Request $request): Response
    {
        $query = User::with('service');

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('lastname', 'like', "%{$search}%")
                  ->orWhere('matricule', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('specialite', 'like', "%{$search}%");
            });
        }

        // Filtre par fonction
        if ($request->filled('fonction')) {
            $query->where('fonction', $request->fonction);
        }

        // Filtre par service
        if ($request->filled('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        // Filtre par statut
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        // Statistiques
        $stats = [
            'total' => User::count(),
            'medecins' => User::where('fonction', 'Médecin')->count(),
            'infirmiers' => User::whereIn('fonction', ['Infirmière', 'Infirmière Chef', 'Sage-femme'])->count(),
            'enService' => User::where('statut', self::STATUT_EN_SERVICE)->count(),
            'enConge' => User::where('statut', self::STATUT_EN_CONGE)->count(),
        ];

        $personnel = $query->latest()->paginate(10)->withQueryString();
        $services = Service::where('actif', true)->get();

        return Inertia::render('dashboard/personnel', [
            'personnel' => $personnel,
            'stats' => $stats,
            'filters' => $request->only(['search', 'fonction', 'service_id', 'statut']),
            'statuts' => self::getStatuts(),
            'fonctions' => self::FONCTIONS,
            'roles' => self::ROLES,
            'services' => $services,
        ]);
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        // Générer un matricule unique
        
        $prefix = match($request->fonction) {
            'Médecin' => 'MED',
            'Infirmière', 'Infirmière Chef' => 'INF',
            'Sage-femme' => 'SF',
            'Pharmacien(ne)' => 'PHAR',
            'Technicien(ne)' => 'TECH',
            default => 'ADM',
        };
        $lastUser = User::where('matricule', 'like', $prefix . '-%')->orderBy('id', 'desc')->first();
        $number = $lastUser ? intval(substr($lastUser->matricule, -3)) + 1 : 1;
        $matricule = $prefix . '-' . str_pad($number, 3, '0', STR_PAD_LEFT);

        $data = $request->validated();

        User::create([
            ...$data,
            'matricule' => $matricule,
            'password' => Hash::make($data['password'] ?? 'password123'),
            'statut' => $data['statut'] ?? self::STATUT_EN_SERVICE,
        ]);

        return redirect()->route('personnel.index')
            ->with('success', 'Personnel ajouté avec succès.');
    }

    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        
        // Si un nouveau mot de passe est fourni
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('personnel.index')
            ->with('success', 'Personnel mis à jour avec succès.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->update(['statut' => self::STATUT_INACTIF]);

        return redirect()->route('personnel.index')
            ->with('success', 'Personnel désactivé avec succès.');
    }
}