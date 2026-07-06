<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use App\Enums\QvctMood;
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
 * Personal QVCT journal entry. Auditable but with `body` excluded from
 * audit (encrypted ciphertext on every audit row would defeat the
 * encryption AND become unreadable after APP_KEY rotation — same
 * pattern as Intervention::report_text).
 *
 * Tenant-scoped via BelongsToStructure; per-user policy in
 * QvctJournalEntryPolicy enforces owner-only or RH+shared semantics.
 *
 * @property string $id
 * @property string $structure_id
 * @property int $user_id
 * @property string $body
 * @property QvctMood $mood
 * @property int $mood_score
 * @property bool $shared_with_rh
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read Structure|null $structure
 * @property-read User|null $user
 *
 * @method static \Database\Factories\QvctJournalEntryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctJournalEntry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctJournalEntry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctJournalEntry onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctJournalEntry query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctJournalEntry whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctJournalEntry whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctJournalEntry whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctJournalEntry whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctJournalEntry whereMood($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctJournalEntry whereMoodScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctJournalEntry whereSharedWithRh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctJournalEntry whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctJournalEntry whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctJournalEntry whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctJournalEntry withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctJournalEntry withoutTrashed()
 *
 * @mixin \Eloquent
 */
class QvctJournalEntry extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'qvct_journal_entries';

    protected $fillable = [
        'structure_id',
        'user_id',
        'body',
        'mood',
        'mood_score',
        'shared_with_rh',
    ];

    protected function casts(): array
    {
        return [
            'body' => 'encrypted',
            'mood' => QvctMood::class,
            'mood_score' => 'integer',
            'shared_with_rh' => 'boolean',
        ];
    }

    /**
     * Audit whitelist — `body` deliberately omitted. Mood + sharing flag
     * are auditable so a privacy regression (e.g. shared_with_rh flipped
     * outside the policy) is forensically traceable.
     */
    protected $auditInclude = [
        'structure_id',
        'user_id',
        'mood',
        'mood_score',
        'shared_with_rh',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
