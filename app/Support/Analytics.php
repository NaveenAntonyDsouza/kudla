<?php

namespace App\Support;

/**
 * Key-moment tracking ("conversions") for GA4 / Google Tag Manager / Meta
 * Pixel / PostHog — whichever the site switched on in SEO Settings.
 *
 * Controllers call Analytics::track() when something worth counting
 * happens (registration steps, photo added, interest sent, purchase…). The
 * event waits in the member's session and is sent from the browser on the
 * next page that renders the tracking partial — most of these actions
 * redirect, so it can't be sent from the request itself.
 *
 * NEVER put personal data in params (names, emails, phones, Matri IDs) —
 * GA4's terms forbid it and the sites hold sensitive data. Counts, step
 * numbers, plan names and amounts only.
 */
final class Analytics
{
    public const SESSION_KEY = 'analytics.events';

    /** Meta Pixel standard events used below ('trackCustom' for the rest). */
    public const META_STANDARD = ['Lead', 'CompleteRegistration', 'Purchase'];

    /**
     * @param  string  $event  GA4-style name, e.g. 'sign_up', 'purchase'
     * @param  array<string, scalar|array>  $params
     * @param  string|null  $metaEvent  Meta Pixel name: a standard event
     *                                  ('Lead', 'CompleteRegistration', 'Purchase')
     *                                  or a custom one (sent with trackCustom)
     */
    public static function track(string $event, array $params = [], ?string $metaEvent = null): void
    {
        try {
            if (! app()->bound('session') || ! request()->hasSession()) {
                return; // console / queue / API — no browser to send it from
            }
            session()->push(self::SESSION_KEY, [
                'name' => $event,
                'params' => $params,
                'meta' => $metaEvent,
                'meta_standard' => in_array($metaEvent, self::META_STANDARD, true),
            ]);
        } catch (\Throwable $e) {
            report($e); // tracking must never break the action itself
        }
    }

    /**
     * Take (and clear) the waiting events. Called by the tracking partial on
     * every page so they don't pile up when no analytics tool is configured.
     *
     * @return list<array{name: string, params: array, meta: ?string, meta_standard: bool}>
     */
    public static function pull(): array
    {
        try {
            return request()->hasSession() ? (array) session()->pull(self::SESSION_KEY, []) : [];
        } catch (\Throwable) {
            return [];
        }
    }
}
