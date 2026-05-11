<?php

declare(strict_types=1);

namespace App\Http\Requests\Qvct;

use App\Http\Requests\BaseFormRequest;
use App\Models\QvctCampaign;

/**
 * Request body for an anonymous response submission. Authorization is
 * scoped to the campaign (`respond` ability on QvctCampaignPolicy) — the
 * service layer enforces the open/closed window separately.
 *
 * Anonymity reminder: this request never reads $this->user() into the
 * payload — only into the gate check. The controller passes only the
 * campaign + answers + optional team_tag to QvctService::recordResponse.
 */
class SubmitQvctResponseRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        $campaign = $this->route('campaign');

        return $campaign instanceof QvctCampaign
            && $this->user()?->can('respond', $campaign);
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1'],
            // Permissive on values — the questionnaire shape lives in
            // qvct_questionnaires.questions; deep validation against it
            // is service-layer territory (different question scales etc).
            'answers.*' => ['required'],
            'team_tag' => ['nullable', 'string', 'max:64'],
        ];
    }
}
