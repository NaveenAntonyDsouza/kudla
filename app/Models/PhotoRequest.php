<?php

namespace App\Models;

use App\Services\MemberEmailService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class PhotoRequest extends Model
{
    protected $fillable = [
        'requester_profile_id',
        'target_profile_id',
        'status',
    ];

    protected static function booted(): void
    {
        // Photo request emails, hooked on the model so the web and app
        // (API) flows both send them without duplicating the call. The
        // service never throws, so a mail problem can't fail the request.
        static::created(function (PhotoRequest $request) {
            if ($request->status === 'pending') {
                DB::afterCommit(fn () => app(MemberEmailService::class)->photoRequested($request));
            }
        });

        static::updated(function (PhotoRequest $request) {
            if ($request->wasChanged('status') && $request->status === 'approved') {
                DB::afterCommit(fn () => app(MemberEmailService::class)->photoRequestApproved($request));
            }
        });
    }

    public function requesterProfile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'requester_profile_id');
    }

    public function targetProfile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'target_profile_id');
    }
}
