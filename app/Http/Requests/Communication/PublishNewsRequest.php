<?php

declare(strict_types=1);

namespace App\Http\Requests\Communication;

use App\Http\Requests\BaseFormRequest;
use App\Models\NewsFeedPost;

class PublishNewsRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', NewsFeedPost::class) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'min:1', 'max:20000'],
        ];
    }
}
