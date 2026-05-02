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

class Document extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'documents';

    protected $fillable = [
        'structure_id',
        'title',
        'description',
        'disk',
        'path',
        'mime_type',
        'size_bytes',
        'version',
        'roles_acl',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'version' => 'integer',
            'roles_acl' => 'array',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * True when the document has no role ACL (structure-wide visibility)
     * OR when the user holds at least one role in the ACL list.
     */
    public function isVisibleTo(User $user): bool
    {
        if (empty($this->roles_acl)) {
            return true;
        }

        $userRoleNames = $user->getRoleNames()->all();

        return ! empty(array_intersect($userRoleNames, $this->roles_acl));
    }
}
