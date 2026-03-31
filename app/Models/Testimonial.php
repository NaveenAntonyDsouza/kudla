<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testimonial extends Model
{
    protected $fillable = [
        'couple_names',
        'story',
        'photo_url',
        'wedding_date',
        'location',
        'submitted_by_user_id',
        'is_visible',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'wedding_date' => 'date',
            'is_visible' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }
}
