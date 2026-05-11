<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * Message model. Auditable but with `body` excluded — encrypted
 * narrative on every audit row would defeat the encryption AND become
 * unreadable after APP_KEY rotation (same reasoning as
 * QvctJournalEntry, Intervention::report_text).
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
