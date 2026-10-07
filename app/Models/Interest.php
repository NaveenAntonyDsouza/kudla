<?php

namespace App\Models;

use App\Support\PhotoVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Interest extends Model
{
    protected static function booted(): void
    {
        // An accepted interest unlocks "after interest accepted" photos —
        // keep photo-visibility answers fresh within the same request.
        static::saved(fn () => PhotoVisibility::invalidate());
        static::deleted(fn () => PhotoVisibility::invalidate());
    }

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
        'sender_last_read_at',
        'receiver_last_read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_starred_by_sender' => 'boolean',
            'is_starred_by_receiver' => 'boolean',
            'is_trashed_by_sender' => 'boolean',
            'is_trashed_by_receiver' => 'boolean',
            'cancelled_at' => 'datetime',
            'sender_last_read_at' => 'datetime',
            'receiver_last_read_at' => 'datetime',
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

    /** Where an interest can be sent from (interests.source) → admin label. */
    public const SOURCES = [
        'profile' => 'Profile page',
        'card' => 'Profile card',
        'mobile_bar' => 'Phone action bar',
        'contact_tab' => 'Contact tab',
        'app' => 'Mobile app',
    ];

    /**
     * Note where a just-sent interest came from. Best-effort, straight to
     * the table: never fails the send, and skips anything not saved.
     */
    public static function recordSource(?self $interest, string $source): void
    {
        if (! $interest?->exists || ! array_key_exists($source, self::SOURCES)) {
            return;
        }

        try {
            DB::table('interests')->where('id', $interest->id)->update(['source' => $source]);
        } catch (\Throwable) {
            // before the migration has run
        }
    }

    /** The newest reply (accept, decline or chat message) — the Chats list preview. */
    public function latestReply(): HasOne
    {
        return $this->hasOne(InterestReply::class)->latestOfMany();
    }

    /**
     * This member has now seen the conversation (Chats unread counts).
     * Written straight to the table so updated_at — the inbox's date — is
     * left alone; never fails the page if the column isn't there yet.
     */
    public function markReadBy(Profile $profile): void
    {
        $column = match ($profile->id) {
            $this->sender_profile_id => 'sender_last_read_at',
            $this->receiver_profile_id => 'receiver_last_read_at',
            default => null,
        };
        if (! $column) {
            return;
        }

        try {
            DB::table('interests')->where('id', $this->id)->update([$column => now()]);
        } catch (\Throwable) {
            // before the migration has run
        }
    }
}
