<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $id
 * @property string $structure_id
 * @property int $author_id
 * @property string $title
 * @property string $body
 * @property string|null $accepted_answer_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read QaAnswer|null $acceptedAnswer
 * @property-read Collection<int, QaAnswer> $answers
 * @property-read int|null $answers_count
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read User|null $author
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\QaQuestionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaQuestion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaQuestion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaQuestion onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaQuestion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaQuestion whereAcceptedAnswerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaQuestion whereAuthorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaQuestion whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaQuestion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaQuestion whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaQuestion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaQuestion whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaQuestion whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaQuestion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaQuestion withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaQuestion withoutTrashed()
 *
 * @mixin \Eloquent
 */
class QaQuestion extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'qa_questions';

    protected $fillable = [
        'structure_id',
        'author_id',
        'title',
        'body',
        'accepted_answer_id',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QaAnswer::class, 'qa_question_id');
    }

    public function acceptedAnswer(): BelongsTo
    {
        return $this->belongsTo(QaAnswer::class, 'accepted_answer_id');
    }
}
