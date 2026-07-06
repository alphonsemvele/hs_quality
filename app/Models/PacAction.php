<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use App\Enums\PacActionStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $id
 * @property string $structure_id
 * @property string $pac_id
 * @property string|null $source_audit_response_id
 * @property string $title
 * @property string|null $description
 * @property int|null $responsible_user_id
 * @property Carbon|null $due_date
 * @property PacActionStatus $status
 * @property string|null $evidence_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $priority
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read Pac|null $pac
 * @property-read User|null $responsibleUser
 * @property-read AuditRunResponse|null $sourceResponse
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\PacActionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PacAction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PacAction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PacAction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PacAction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PacAction whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PacAction whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PacAction whereEvidenceUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PacAction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PacAction wherePacId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PacAction wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PacAction whereResponsibleUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PacAction whereSourceAuditResponseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PacAction whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PacAction whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PacAction whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PacAction whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PacAction extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;

    protected $table = 'pac_actions';

    protected $fillable = [
        'structure_id',
        'pac_id',
        'source_audit_response_id',
        'title',
        'description',
        'responsible_user_id',
        'due_date',
        'status',
        'priority',
        'evidence_url',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'status' => PacActionStatus::class,
        ];
    }

    public function pac(): BelongsTo
    {
        return $this->belongsTo(Pac::class, 'pac_id');
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function sourceResponse(): BelongsTo
    {
        return $this->belongsTo(AuditRunResponse::class, 'source_audit_response_id');
    }
}
