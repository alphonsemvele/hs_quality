<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\QvctExchangeAddresseeRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Qvct\ScheduleExchangeRequestRequest;
use App\Http\Requests\Qvct\StoreExchangeRequestRequest;
use App\Models\QvctExchangeRequest;
use App\Services\ExchangeRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class QvctExchangeRequestController extends Controller
{
    public function __construct(private readonly ExchangeRequestService $service) {}

    public function store(StoreExchangeRequestRequest $request): JsonResponse
    {
        $exchange = $this->service->create(
            $request->user(),
            QvctExchangeAddresseeRole::from($request->validated('addressee_role')),
            $request->validated('message'),
        );

        return response()->json($exchange, 201);
    }

    public function mine(Request $request): JsonResponse
    {
        $this->authorize('viewAny', QvctExchangeRequest::class);

        return response()->json([
            'data' => $this->service->outgoingFor($request->user()),
        ]);
    }

    public function incoming(Request $request): JsonResponse
    {
        $this->authorize('viewAny', QvctExchangeRequest::class);

        return response()->json([
            'data' => $this->service->incomingFor($request->user()),
        ]);
    }

    public function accept(Request $request, QvctExchangeRequest $exchangeRequest): JsonResponse
    {
        $this->authorize('accept', $exchangeRequest);

        return response()->json($this->service->accept($exchangeRequest, $request->user()));
    }

    public function schedule(ScheduleExchangeRequestRequest $request, QvctExchangeRequest $exchangeRequest): JsonResponse
    {
        $when = Carbon::parse($request->validated('scheduled_at'));

        return response()->json($this->service->schedule($exchangeRequest, $when));
    }

    public function close(Request $request, QvctExchangeRequest $exchangeRequest): JsonResponse
    {
        $this->authorize('close', $exchangeRequest);

        return response()->json($this->service->close(
            $exchangeRequest,
            $request->input('reason'),
        ));
    }
}
