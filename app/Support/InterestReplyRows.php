<?php

namespace App\Support;

use App\Models\Interest;
use Illuminate\Support\Collection;

/**
 * Interests waiting for an answer, one table row each, with Accept / Decline
 * links that work without signing in (Interest::emailReplyUrl). Shared by the
 * reply reminder and the weekly match email (Nov 2026), so both look the same.
 */
final class InterestReplyRows
{
    /**
     * @param  Collection<int, Interest>  $interests  with senderProfile (+ locationInfo, educationDetail) and receiverProfile.user loaded
     * @return string  `<tr>` rows; every value is escaped here
     */
    public static function html(Collection $interests): string
    {
        return $interests->map(function (Interest $interest) {
            $sender = $interest->senderProfile;
            $summary = MemberSummary::line($sender);
            $days = (int) $interest->created_at?->diffInDays(now());

            return '<tr><td style="padding:12px 0;border-bottom:1px solid #e5e7eb;">'
                . '<strong>' . e($sender?->matri_id) . '</strong>'
                . ($summary !== '' ? ' <span style="color:#6b7280;">· ' . e($summary) . '</span>' : '')
                . '<br><span style="font-size:13px;color:#6b7280;">Sent ' . e($days === 1 ? '1 day' : "{$days} days") . ' ago</span>'
                . '</td><td style="padding:12px 0;border-bottom:1px solid #e5e7eb;text-align:right;white-space:nowrap;">'
                . '<a href="' . e($interest->emailReplyUrl('accept')) . '" style="color:#15803d;font-weight:600;text-decoration:none;margin-right:12px;">Accept</a>'
                . '<a href="' . e($interest->emailReplyUrl('decline')) . '" style="color:#b91c1c;font-weight:600;text-decoration:none;">Decline</a>'
                . '</td></tr>';
        })->implode('');
    }
}
