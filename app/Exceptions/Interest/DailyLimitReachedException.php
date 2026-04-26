<?php

namespace App\Exceptions\Interest;

/**
 * Thrown by InterestService when the sender has hit their plan's daily
 * interest cap.
 *
 * The InterestController catches this and renders it as the canonical
 * `DAILY_LIMIT_REACHED` (HTTP 429) error envelope — distinct from the
 * generic `INVALID_INTEREST` 422 used for other service failures —
 * because Flutter switches on the code to trigger the upgrade dialog.
 *
 * Contract reference: docs/mobile-app/reference/error-codes.md
 */
class DailyLimitReachedException extends \RuntimeException
{
    public function __construct(
        public readonly int $limit,
        public readonly int $used,
        ?string $message = null,
    ) {
        parent::__construct(
            $message
                ?? "Daily interest limit reached ({$limit}/day). Upgrade your plan for more interests."
        );
    }
}
