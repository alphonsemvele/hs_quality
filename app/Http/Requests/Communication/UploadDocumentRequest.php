<?php

declare(strict_types=1);

namespace App\Http\Requests\Communication;

use App\Http\Requests\BaseFormRequest;
use App\Models\Document;

class UploadDocumentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Document::class) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        // 25 MB max — must match DocumentLibraryService::MAX_BYTES.
        // Allowed MIMEs: PDF, Office docs, common images. The service
        // double-checks the MIME after upload (defense in depth).
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'file' => [
                'required',
                'file',
                'max:25600', // 25 MB in kilobytes
                'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,image/jpeg,image/png,image/webp',
            ],
            'roles_acl' => ['nullable', 'array'],
            'roles_acl.*' => ['string', 'max:64'],
        ];
    }
}
