<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Idempotency record for incoming webhook events.
 *
 * One row per (gateway, event_id) pair. The unique constraint at the
 * DB layer is what guarantees dedupe — gateway services catch the
 * QueryException on duplicate insert and return 200 OK without
 * re-processing the event.
 *
 * Status values:
 *   - processed   we acted on this event (e.g. activated subscription)
 *   - duplicate   we'd seen this event id before; the duplicate row's
 *                 attempted insert was rejected (won't actually appear
 *                 in this column — the gateway service updates the
 *                 ORIGINAL row's status if needed).
 *   - ignored     known-but-uninteresting event type (e.g. payment.authorized
 *                 when we only care about payment.captured)
 *   - failed      handler threw mid-processing; row kept for debugging
 *
 * Reference:
 *   docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-05-razorpay-webhook.md
 */
class WebhookEvent extends Model
{
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_IGNORED = 'ignored';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'gateway',
        'event_id',
        'event_type',
        'status',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
