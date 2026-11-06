<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * A Contact Us message (website form, app). Messages from one sender are grouped
 * by email address (6 Nov 2026: one sender used a different phone number on every
 * message; on Kudla 20 waiting messages came from 11 people, on Crasta 76 from 17):
 * the inbox's New tab shows one row per sender, Reply and Close answer all of the
 * sender's waiting messages, and the counts are of people waiting.
 */
class ContactSubmission extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
        'admin_reply',
        'replied_at',
        'assigned_to',
        'admin_notes',
        'ip_address',
    ];

    /** Messages still waiting for staff: Reply and Close work on these. */
    public const OPEN = ['new', 'in_progress'];

    /** The same text from the same address within this many hours is saved once (a second press, a bot). */
    public const REPEAT_HOURS = 24;

    protected function casts(): array
    {
        return [
            'replied_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN, true);
    }

    /** Messages from one sender (email address, any letter case). */
    public function scopeFromSender(Builder $query, string $email): Builder
    {
        return $query->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))]);
    }

    /** One row per sender: each sender's newest message among $statuses (any status when null). */
    public function scopeNewestPerSender(Builder $query, ?array $statuses = null): Builder
    {
        $table = $this->getTable();

        return $query->whereNotExists(fn ($newer) => $newer->selectRaw('1')->from($table . ' as newer')
            ->whereRaw("LOWER(newer.email) = LOWER({$table}.email)")
            ->when($statuses !== null, fn ($q) => $q->whereIn('newer.status', $statuses))
            ->where(fn ($q) => $q->whereColumn('newer.created_at', '>', "{$table}.created_at")
                ->orWhere(fn ($tie) => $tie->whereColumn('newer.created_at', "{$table}.created_at")
                    ->whereColumn('newer.id', '>', "{$table}.id"))));
    }

    /**
     * Adds, per row, how many of the sender's messages are waiting (sender_waiting)
     * and when the first of them came in (sender_waiting_since).
     */
    public function scopeWithSenderWaiting(Builder $query): Builder
    {
        $table = $this->getTable();
        $waiting = fn () => DB::table($table . ' as same')
            ->whereRaw("LOWER(same.email) = LOWER({$table}.email)")
            ->where('same.status', 'new');

        return $query->select("{$table}.*")
            ->selectSub($waiting()->selectRaw('COUNT(*)'), 'sender_waiting')
            ->selectSub($waiting()->selectRaw('MIN(same.created_at)'), 'sender_waiting_since');
    }

    /** This sender's messages still waiting (this one included), oldest first. */
    public function openFromSender(): Collection
    {
        return static::fromSender((string) $this->email)->whereIn('status', self::OPEN)
            ->orderBy('created_at')->orderBy('id')->get();
    }

    /** Everything this sender sent, newest first. */
    public function allFromSender(): Collection
    {
        return static::fromSender((string) $this->email)->orderByDesc('created_at')->orderByDesc('id')->get();
    }

    /** People waiting for an answer (senders with a new message): the menu badge, Work Queue and Scorecard. */
    public static function sendersWaiting(): int
    {
        return (int) static::where('status', 'new')->distinct()->count(DB::raw('LOWER(email)'));
    }

    /** The message this address already sent with the same text in the last REPEAT_HOURS, if any. */
    public static function findRepeat(string $email, string $message): ?self
    {
        $text = self::comparable($message);

        return static::fromSender($email)->where('created_at', '>=', now()->subHours(self::REPEAT_HOURS))
            ->orderByDesc('id')->get()
            ->first(fn (self $sent): bool => self::comparable((string) $sent->message) === $text);
    }

    private static function comparable(string $text): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $text)));
    }
}
