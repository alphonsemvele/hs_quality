<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Enums\CustomOptionField;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreCustomOptionRequest;
use App\Http\Requests\Settings\UpdateCustomOptionRequest;
use App\Models\CustomOption;
use App\Services\CustomOptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomOptionController extends Controller
{
    public function __construct(private readonly CustomOptionService $service) {}

    /**
     * List all options (enum defaults merged with tenant customs) for a field.
     * Used by React to populate dropdowns and the options-management modal.
     */
    public function index(Request $request, string $fieldKey): JsonResponse
    {
        $this->authorize('viewAny', CustomOption::class);

        // Validate that this field_key is on the whitelist.
        $field = CustomOptionField::tryFrom($fieldKey);
        abort_unless($field !== null, 422, 'Ce champ ne prend pas en charge les options personnalisées.');

        $items = CustomOption::query()
            ->forField($fieldKey)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get(['id', 'value', 'label', 'sort_order', 'is_active']);

        return response()->json(['items' => $items]);
    }

    public function store(StoreCustomOptionRequest $request): JsonResponse
    {
        // structure_id is set automatically by the BelongsToStructure trait.
        $option = CustomOption::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'sort_order' => $request->validated('sort_order', 0),
        ]);

        return response()->json($option->only(['id', 'value', 'label', 'sort_order', 'is_active']), 201);
    }

    public function update(UpdateCustomOptionRequest $request, CustomOption $option): JsonResponse
    {
        $option->update($request->validated());

        return response()->json($option->fresh()->only(['id', 'value', 'label', 'sort_order', 'is_active']));
    }

    public function destroy(Request $request, CustomOption $option): JsonResponse
    {
        $this->authorize('delete', $option);

        $option->delete();

        return response()->json(null, 204);
    }
}
