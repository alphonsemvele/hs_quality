<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $structure_id
 * @property string $discussion_group_id
 * @property int $user_id
 * @property string $role
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read DiscussionGroup|null $group
 * @property-read Structure|null $structure
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscussionGroupMember newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscussionGroupMember newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscussionGroupMember query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscussionGroupMember whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscussionGroupMember whereDiscussionGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscussionGroupMember whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscussionGroupMember whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscussionGroupMember whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscussionGroupMember whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscussionGroupMember whereUserId($value)
 *
 * @mixin \Eloquent
 */
class DiscussionGroupMember extends Model
{
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;

    protected $table = 'discussion_group_members';

    protected $fillable = [
        'structure_id',
        'discussion_group_id',
        'user_id',
        'role',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(DiscussionGroup::class, 'discussion_group_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
