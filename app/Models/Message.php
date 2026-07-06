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
 * Message model. Auditable but with `body` excluded — encrypted
 * narrative on every audit row would defeat the encryption AND become
 * unreadable after APP_KEY rotation (same reasoning as
 * QvctJournalEntry, Intervention::report_text).
 *
 * @property string $id
 * @property string $structure_id
 * @property string $discussion_group_id
 * @property int $author_id
 * @property string $body
 * @property array<array-key, mixed>|null $attachments
 * @property Carbon|null $edited_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read User|null $author
 * @property-read DiscussionGroup|null $group
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\MessageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereAttachments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereAuthorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereDiscussionGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereEditedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Message extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'messages';

    protected $fillable = [
        'structure_id',
        'discussion_group_id',
        'author_id',
        'body',
        'attachments',
        'edited_at',
    ];

    protected function casts(): array
    {
        return [
            'body' => 'encrypted',
            'attachments' => 'array',
            'edited_at' => 'datetime',
        ];
    }

    /**
     * Audit whitelist — `body` deliberately omitted; metadata fields
     * (who, when, edits) are auditable for moderation forensics.
     */
    protected $auditInclude = [
        'structure_id',
        'discussion_group_id',
        'author_id',
        'edited_at',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(DiscussionGroup::class, 'discussion_group_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
