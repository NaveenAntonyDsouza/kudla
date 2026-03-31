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
}
