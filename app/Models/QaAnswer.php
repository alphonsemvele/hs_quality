<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $id
 * @property string $structure_id
 * @property string $qa_question_id
 * @property int $author_id
 * @property string $body
 * @property int $upvotes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read User|null $author
 * @property-read QaQuestion|null $question
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\QaAnswerFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaAnswer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaAnswer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaAnswer onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaAnswer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaAnswer whereAuthorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaAnswer whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaAnswer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaAnswer whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaAnswer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaAnswer whereQaQuestionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaAnswer whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaAnswer whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaAnswer whereUpvotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaAnswer withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaAnswer withoutTrashed()
 *
 * @mixin \Eloquent
 */
class QaAnswer extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'qa_answers';

    protected $fillable = [
        'structure_id',
        'qa_question_id',
        'author_id',
        'body',
        'upvotes',
    ];

    protected function casts(): array
    {
        return [
            'upvotes' => 'integer',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QaQuestion::class, 'qa_question_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
