<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\ActionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class ActionAmelioration extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;

    protected $table = 'actions_amelioration';

    protected $fillable = [
        'structure_id',
        'plan_amelioration_id',
        'description',
        'responsable',
        'echeance',
        'statut',
        'realise_at',
    ];

    protected function casts(): array
    {
        return [
            'echeance' => 'date',
            'realise_at' => 'datetime',
            'statut' => ActionStatus::class,
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanAmelioration::class, 'plan_amelioration_id');
    }
}
