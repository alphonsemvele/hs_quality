<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\QvctMood;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
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
