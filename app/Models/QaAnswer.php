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

class QaAnswer extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'qa_answers';

    protected $fillable = [
        'structure_id',
        'qa_question_id',
        'author_id',
        'body',
        'upvotes',
    ];

    protected function casts(): array
    {
        return [
            'upvotes' => 'integer',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QaQuestion::class, 'qa_question_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
