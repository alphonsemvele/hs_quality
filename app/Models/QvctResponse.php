<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Anonymous QVCT response. NOT Auditable — every audit row would need to
 * carry the response payload, defeating the anonymity guarantee. Tenant
 * scoping (structure_id) and BelongsToStructure cover the only access
 * control that matters here. Soft-delete is intentionally NOT used —
 * deletion of an anonymous response is a hard delete (RGPD erasure
 * paradoxically does not apply to truly anonymous data, but operators
 * may still want to drop bad-faith fill rows).
 *
 * @property string $id
 * @property string $structure_id
 * @property string $campaign_id
 * @property array<array-key, mixed> $answers
 * @property string|null $team_tag
 * @property Carbon $submitted_at
 * @property-read QvctCampaign|null $campaign
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\QvctResponseFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctResponse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctResponse newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctResponse query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctResponse whereAnswers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctResponse whereCampaignId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctResponse whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctResponse whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctResponse whereSubmittedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctResponse whereTeamTag($value)
 *
 * @mixin \Eloquent
 */
class QvctResponse extends Model
{
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;

    public $timestamps = false;

    protected $table = 'qvct_responses';

    protected $fillable = [
        'structure_id',
        'campaign_id',
        'answers',
        'team_tag',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(QvctCampaign::class, 'campaign_id');
    }
}
