<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/** See the email_logs migration. Written only through App\Support\EmailLogger. */
class EmailLog extends Model
{
    use Prunable;

    public const UPDATED_AT = null;

    public const KEEP_DAYS = 180;

    protected $fillable = ['event', 'email_type', 'subject', 'recipient', 'user_id', 'error'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(self::KEEP_DAYS));
    }
}
