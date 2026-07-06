<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToStructure;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $structure_id
 * @property int $user_id
 * @property string $discussion_group_id
 * @property string|null $last_read_message_id
 * @property Carbon|null $last_read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read DiscussionGroup|null $group
 * @property-read Message|null $lastReadMessage
 * @property-read Structure|null $structure
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageReadCursor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageReadCursor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageReadCursor query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageReadCursor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageReadCursor whereDiscussionGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageReadCursor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageReadCursor whereLastReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageReadCursor whereLastReadMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageReadCursor whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageReadCursor whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageReadCursor whereUserId($value)
 *
 * @mixin \Eloquent
 */
class MessageReadCursor extends Model
{
    use BelongsToStructure;
    use HasUuids;

    protected $table = 'message_read_cursors';

    protected $fillable = [
        'structure_id',
        'user_id',
        'discussion_group_id',
        'last_read_message_id',
        'last_read_at',
    ];

    protected function casts(): array
    {
        return [
            'last_read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(DiscussionGroup::class, 'discussion_group_id');
    }

    public function lastReadMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_read_message_id');
    }
}
