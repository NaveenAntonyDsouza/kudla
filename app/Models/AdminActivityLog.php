<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminActivityLog extends Model
{
    protected $table = 'admin_activity_log';

    protected $fillable = [
        'admin_user_id',
        'action',
        'model_type',
        'model_id',
        'changes',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'model_id' => 'integer',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    /**
     * Actions the system takes by itself (scheduled jobs, a member answering
     * an email) have no admin. admin_user_id is NOT NULL in the live schema,
     * so those rows failed silently (kudla, 5–7 Oct 2026); they're recorded
     * under the site's main admin account, as the reminder commands already did.
     */
    protected static function booted(): void
    {
        static::creating(function (self $log) {
            $log->admin_user_id ??= self::systemAdminId();
        });
    }

    public static function systemAdminId(): ?int
    {
        try {
            return User::where('role', 'admin')->orderBy('id')->value('id');
        } catch (\Throwable) {
            return null;
        }
    }
}
