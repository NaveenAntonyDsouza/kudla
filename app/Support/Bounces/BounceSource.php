<?php

namespace App\Support\Bounces;

interface BounceSource
{
    /**
     * Bounce notices received since the date, oldest first.
     *
     * @return iterable<array{uid: int, received: ?string, raw: string}>
     */
    public function bounces(\DateTimeInterface $since): iterable;
}
