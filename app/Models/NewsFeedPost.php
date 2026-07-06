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
 * @property string $title
 * @property string $body
 * @property int $author_id
 * @property bool $pinned
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read User|null $author
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\NewsFeedPostFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsFeedPost newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsFeedPost newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsFeedPost onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsFeedPost query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsFeedPost whereArchivedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsFeedPost whereAuthorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsFeedPost whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsFeedPost whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsFeedPost whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsFeedPost whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsFeedPost wherePinned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsFeedPost whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsFeedPost whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsFeedPost whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsFeedPost withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsFeedPost withoutTrashed()
 *
 * @mixin \Eloquent
 */
class NewsFeedPost extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'news_feed_posts';

    protected $fillable = [
        'structure_id',
        'title',
        'body',
        'author_id',
        'pinned',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'pinned' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
