<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use Database\Factories\QaAnswerVoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One row per (user, answer) upvote on the Q&A forum. The unique constraint
 * on (qa_answer_id, user_id) makes voting idempotent at the storage layer;
 * the denormalised QaAnswer.upvotes counter is kept in sync by QaService.
 *
 * bigint PK (not UUID) — matches the `qa_answer_votes` pivot migration.
 * Tenant-scoped via the global structure scope. Not Auditable: a vote is a
 * low-sensitivity engagement signal, not health or QVCT survey data.
 *
 * @property int $id
 * @property string $structure_id
 * @property string $qa_answer_id
 * @property int $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read QaAnswer|null $answer
 * @property-read Structure|null $structure
 * @property-read User|null $user
 *
 * @method static \Database\Factories\QaAnswerVoteFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaAnswerVote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaAnswerVote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaAnswerVote query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaAnswerVote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaAnswerVote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaAnswerVote whereQaAnswerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaAnswerVote whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaAnswerVote whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QaAnswerVote whereUserId($value)
 *
 * @mixin \Eloquent
 */
class QaAnswerVote extends Model
{
    use BelongsToStructure;

    /** @use HasFactory<QaAnswerVoteFactory> */
    use HasFactory;

    protected $table = 'qa_answer_votes';

    protected $fillable = [
        'structure_id',
        'qa_answer_id',
        'user_id',
    ];

    public function answer(): BelongsTo
    {
        return $this->belongsTo(QaAnswer::class, 'qa_answer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
