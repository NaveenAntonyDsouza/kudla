<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Interest extends Model
{
    protected $fillable = [
        'sender_profile_id',
        'receiver_profile_id',
        'template_id',
        'custom_message',
        'status',
        'is_starred_by_sender',
        'is_starred_by_receiver',
        'is_trashed_by_sender',
        'is_trashed_by_receiver',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'is_starred_by_sender' => 'boolean',
            'is_starred_by_receiver' => 'boolean',
            'is_trashed_by_sender' => 'boolean',
            'is_trashed_by_receiver' => 'boolean',
            'cancelled_at' => 'datetime',
        ];
    }

    public function senderProfile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'sender_profile_id');
    }

    public function receiverProfile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'receiver_profile_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(InterestReply::class);
    }
}
