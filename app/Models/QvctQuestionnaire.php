<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\QvctFrequency;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class QvctQuestionnaire extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'qvct_questionnaires';

    protected $fillable = [
        'structure_id',
        'title',
        'version',
        'frequency',
        'questions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'frequency' => QvctFrequency::class,
            'questions' => 'array',
            'is_active' => 'boolean',
            'version' => 'integer',
        ];
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(QvctCampaign::class, 'questionnaire_id');
    }
}
