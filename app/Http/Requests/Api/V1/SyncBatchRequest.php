<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\BaseFormRequest;

/**
 * Validates a batch of queued mobile operations posted to /api/v1/sync/batch.
 *
 * Per-operation payload validation lives in SyncBatchService (each `kind`
 * has its own shape). Here we enforce only the envelope: the array, the
 * required keys, and a hard cap on batch size to bound memory + work.
 *
 * The cap (200) matches one offline workday for an intervenant
 * (≈8 visits × multiple events each). Batches larger than this are
 * almost always client bugs (corrupt queue) and should be rejected fast
 * rather than consume a worker.
 */
class SyncBatchRequest extends BaseFormRequest
{
    public const MAX_OPERATIONS_PER_BATCH = 200;

    public function authorize(): bool
    {
        // Sanctum auth + tenant scope are enforced by route middleware.
        // Per-operation Policy checks happen inside the service.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'operations' => ['required', 'array', 'min:1', 'max:'.self::MAX_OPERATIONS_PER_BATCH],
            'operations.*.client_op_id' => ['required', 'string', 'uuid'],
            'operations.*.kind' => ['required', 'string', 'in:'.implode(',', [
                'intervention.check_in',
                'intervention.check_out',
                'intervention.cancel',
                'intervention.submit_report',
                'incident.create',
                'qvct.submit_response',
            ])],
            'operations.*.resource_id' => ['nullable', 'string'],
            'operations.*.client_timestamp' => ['nullable', 'date'],
            // `present` (not `required`) so an empty `payload: {}` is valid.
            // Some ops (e.g. intervention.check_in without GPS) carry no fields.
            'operations.*.payload' => ['present', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'operations.required' => 'A batch must contain at least one operation.',
            'operations.max' => sprintf(
                'Batches are limited to %d operations; split into multiple requests.',
                self::MAX_OPERATIONS_PER_BATCH,
            ),
            'operations.*.client_op_id.uuid' => 'Each operation needs a UUID client_op_id (used to correlate the per-op response).',
            'operations.*.kind.in' => 'Unknown operation kind. Supported: intervention.check_in/check_out/cancel/submit_report, incident.create, qvct.submit_response.',
        ];
    }
}
