<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\PacActionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

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
