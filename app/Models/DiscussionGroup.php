<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $id
 * @property string $structure_id
 * @property string $title
 * @property string|null $description
 * @property string $kind
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read Collection<int, User> $members
 * @property-read int|null $members_count
 * @property-read Collection<int, Message> $messages
 * @property-read int|null $messages_count
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\DiscussionGroupFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscussionGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscussionGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscussionGroup onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscussionGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscussionGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscussionGroup whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscussionGroup whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscussionGroup whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscussionGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscussionGroup whereKind($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscussionGroup whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscussionGroup whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscussionGroup whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscussionGroup withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscussionGroup withoutTrashed()
 *
 * @mixin \Eloquent
 */
class DiscussionGroup extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'discussion_groups';

    protected $fillable = [
        'structure_id',
        'title',
        'description',
        'kind',
        'created_by',
    ];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'discussion_group_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'discussion_group_id');
    }
}
