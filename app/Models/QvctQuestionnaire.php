<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use App\Enums\QvctFrequency;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $id
 * @property string $structure_id
 * @property string $title
 * @property int $version
 * @property QvctFrequency $frequency
 * @property array<array-key, mixed> $questions
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read Collection<int, QvctCampaign> $campaigns
 * @property-read int|null $campaigns_count
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\QvctQuestionnaireFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctQuestionnaire newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctQuestionnaire newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctQuestionnaire onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctQuestionnaire query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctQuestionnaire whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctQuestionnaire whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctQuestionnaire whereFrequency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctQuestionnaire whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctQuestionnaire whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctQuestionnaire whereQuestions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctQuestionnaire whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctQuestionnaire whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctQuestionnaire whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctQuestionnaire whereVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctQuestionnaire withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctQuestionnaire withoutTrashed()
 *
 * @mixin \Eloquent
 */
class QvctQuestionnaire extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'qvct_questionnaires';

    protected $fillable = [
        'structure_id',
        'title',
        'version',
        'frequency',
        'questions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'frequency' => QvctFrequency::class,
            'questions' => 'array',
            'is_active' => 'boolean',
            'version' => 'integer',
        ];
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(QvctCampaign::class, 'questionnaire_id');
    }
}
