<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One message to or from the office number through the WhatsApp Cloud API. */
class WhatsAppMessage extends Model
{
    protected $table = 'whatsapp_messages';

    public const IN = 'in';
    public const OUT = 'out';

    protected $fillable = [
        'direction', 'wa_message_id', 'wa_id', 'contact_name', 'profile_id', 'type', 'body',
        'media_id', 'media_mime', 'profile_photo_id', 'status', 'note', 'sent_by', 'read_at',
    ];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
