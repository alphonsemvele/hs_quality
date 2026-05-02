<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscussionGroupMember extends Model
{
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;

    protected $table = 'discussion_group_members';

    protected $fillable = [
        'structure_id',
        'discussion_group_id',
        'user_id',
        'role',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(DiscussionGroup::class, 'discussion_group_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
