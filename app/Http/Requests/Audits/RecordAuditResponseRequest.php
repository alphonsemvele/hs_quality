<?php

declare(strict_types=1);

namespace App\Http\Requests\Audits;

use App\Enums\AuditItemScale;
use App\Http\Requests\BaseFormRequest;
use App\Models\AuditGridItem;
use App\Models\AuditRunResponse;
use Illuminate\Contracts\Validation\Validator;

class RecordAuditResponseRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AuditRunResponse::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'audit_grid_item_id' => ['required', 'string', 'exists:audit_grid_items,id'],
            'score' => ['nullable', 'numeric', 'min:0'],
            'cotation' => ['nullable', 'string', 'in:A,B,C,D,NA'],
            'comment' => ['nullable', 'string', 'max:5000'],
            'evidence_url' => ['nullable', 'string', 'max:1024'],
        ];
    }

    /**
     * For HAS-cotation items, the cotation field is required (frontend
     * sends it; we reject score-only updates). For other scales, score
     * is required and cotation must be absent.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $itemId = $this->input('audit_grid_item_id');
            if (! is_string($itemId)) {
                return;
            }

            $item = AuditGridItem::query()->find($itemId);
            if ($item === null) {
                return; // exists rule will already fail
            }

            if ($item->scale === AuditItemScale::HasCotation) {
                if ($this->input('cotation') === null) {
                    $v->errors()->add('cotation', 'La cotation A/B/C/D/NA est requise pour cette exigence.');
                }
            } else {
                if ($this->input('cotation') !== null) {
                    $v->errors()->add('cotation', 'La cotation n\'est valide que pour les exigences en échelle HAS.');
                }
            }
        });
    }
}
