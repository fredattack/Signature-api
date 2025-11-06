<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    use HasFactory, HasUuids;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'subject',
        'message',
        'status',
    ];

    /**
     * @return BelongsTo<User, Feedback>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
