<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BeneficiaryResource;
use App\Models\Beneficiary;
use App\Services\BeneficiaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BeneficiaryController extends Controller
{
    public function __construct(
        private readonly BeneficiaryService $service,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Beneficiary::class);

        // Wave 1 / H2 — assigned-only scope for intervenants on mobile.
        // Previously returned every tenant beneficiary regardless of the
        // user's view permission, leaking PII (address, phone, GIR) of
        // unassigned beneficiaries.
        return BeneficiaryResource::collection(
            $this->service->listForUser(request()->user())
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->paginate(100),
        );
    }

    public function show(Beneficiary $beneficiary): JsonResponse
    {
        $this->authorize('view', $beneficiary);

        return response()->json(new BeneficiaryResource($beneficiary));
    }
}
