<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\StructureStatus;
use App\Enums\StructureTier;
use App\Enums\StructureType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Structures\StoreStructureRequest;
use App\Http\Requests\Admin\Structures\UpdateStructureRequest;
use App\Models\Structure;
use App\Services\StructureService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Platform operator surface for managing the tenants themselves.
 *
 * Gated by the `super_admin` middleware (registered in bootstrap/app.php) AND
 * the StructurePolicy. Tenant-scoped users 404 on every endpoint here.
 */
class StructureController extends Controller
{
    public function __construct(
        private readonly StructureService $service,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Structure::class);

        $structures = Structure::query()
            ->orderBy('name')
            ->paginate(25)
            ->through(fn (Structure $s) => $this->summary($s));

        return Inertia::render('admin/structures/index', [
            'structures' => $structures,
            'enums' => $this->enumOptions(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Structure::class);

        return Inertia::render('admin/structures/create', [
            'enums' => $this->enumOptions(),
        ]);
    }

    public function store(StoreStructureRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $result = $this->service->provision(
            structureData: [
                'code' => $data['code'],
                'name' => $data['name'],
                'type' => $data['type'],
                'tier' => $data['tier'] ?? StructureTier::Essential->value,
                'address' => $data['address'] ?? null,
                'siret' => $data['siret'] ?? null,
            ],
            dirigeantData: $data['dirigeant'],
        );

        return redirect()->route('admin.structures.show', $result['structure'])
            ->with('success', sprintf(
                'Structure "%s" provisionnée. Lien de réinitialisation envoyé à %s.',
                $result['structure']->name,
                $result['dirigeant']->email,
            ))
            ->with('password_reset_url', $result['password_reset_url']);
    }

    public function show(Structure $structure): Response
    {
        $this->authorize('view', $structure);

        return Inertia::render('admin/structures/show', [
            'structure' => $this->detail($structure),
            'enums' => $this->enumOptions(),
        ]);
    }

    public function edit(Structure $structure): Response
    {
        $this->authorize('update', $structure);

        return Inertia::render('admin/structures/edit', [
            'structure' => $this->detail($structure),
            'enums' => $this->enumOptions(),
        ]);
    }

    public function update(UpdateStructureRequest $request, Structure $structure): RedirectResponse
    {
        $structure->update($request->validated());

        return redirect()->route('admin.structures.show', $structure)
            ->with('success', 'Structure mise à jour.');
    }

    public function destroy(Structure $structure): RedirectResponse
    {
        $this->authorize('delete', $structure);

        $structure->delete();

        return redirect()->route('admin.structures.index')
            ->with('success', 'Structure supprimée.');
    }

    public function suspend(Structure $structure): RedirectResponse
    {
        $this->authorize('update', $structure);

        $this->service->suspend($structure);

        return back()->with('success', 'Structure suspendue.');
    }

    public function reactivate(Structure $structure): RedirectResponse
    {
        $this->authorize('update', $structure);

        $this->service->reactivate($structure);

        return back()->with('success', 'Structure réactivée.');
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Structure $structure): array
    {
        return [
            'id' => $structure->id,
            'code' => $structure->code,
            'name' => $structure->name,
            'type' => $structure->type->value,
            'type_label' => $structure->type->label(),
            'tier' => $structure->tier->value,
            'tier_label' => $structure->tier->label(),
            'status' => $structure->status->value,
            'status_label' => $structure->status->label(),
            'created_at' => $structure->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(Structure $structure): array
    {
        return [
            ...$this->summary($structure),
            'address' => $structure->address,
            'siret' => $structure->siret,
            'updated_at' => $structure->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, list<array{value: string, label: string}>>
     */
    private function enumOptions(): array
    {
        return [
            'types' => collect(StructureType::cases())
                ->map(fn (StructureType $t) => ['value' => $t->value, 'label' => $t->label()])
                ->all(),
            'tiers' => collect(StructureTier::cases())
                ->map(fn (StructureTier $t) => ['value' => $t->value, 'label' => $t->label()])
                ->all(),
            'statuses' => collect(StructureStatus::cases())
                ->map(fn (StructureStatus $s) => ['value' => $s->value, 'label' => $s->label()])
                ->all(),
        ];
    }
}
