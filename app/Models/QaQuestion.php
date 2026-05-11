<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class QaQuestion extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'qa_questions';

    protected $fillable = [
        'structure_id',
        'author_id',
        'title',
        'body',
        'accepted_answer_id',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QaAnswer::class, 'qa_question_id');
    }

    public function acceptedAnswer(): BelongsTo
    {
        return $this->belongsTo(QaAnswer::class, 'accepted_answer_id');
    }
}
