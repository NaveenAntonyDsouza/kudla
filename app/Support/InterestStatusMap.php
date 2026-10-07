<?php

namespace App\Support;

use App\Models\Interest;
use App\Models\Profile;

/**
 * Where the signed-in member stands with each profile on a page of cards
 * (search, matches, dashboard): one query per request instead of one per
 * card. Same rule as InterestService::getStatus — sent wins over received.
 */
class InterestStatusMap
{
    private const LIVE = ['pending', 'accepted', 'declined'];

    /** @return array<int, array{direction: string, status: string, interest_id: int}> keyed by the other profile's id */
    public static function for(Profile $viewer): array
    {
        $key = 'interest_status_map.'.$viewer->id;
        if (request()->attributes->has($key)) {
            return request()->attributes->get($key);
        }

        $map = [];
        try {
            $rows = Interest::query()
                ->where(fn ($q) => $q->where('sender_profile_id', $viewer->id)->orWhere('receiver_profile_id', $viewer->id))
                ->whereIn('status', self::LIVE)
                ->orderBy('id')
                ->get(['id', 'sender_profile_id', 'receiver_profile_id', 'status']);

            foreach ($rows as $row) {
                $sent = $row->sender_profile_id === $viewer->id;
                $other = $sent ? $row->receiver_profile_id : $row->sender_profile_id;
                // a sent interest takes priority over a received one; newest wins within a direction
                if (isset($map[$other]) && $map[$other]['direction'] === 'sent' && ! $sent) {
                    continue;
                }
                $map[$other] = ['direction' => $sent ? 'sent' : 'received', 'status' => $row->status, 'interest_id' => $row->id];
            }
        } catch (\Throwable) {
            $map = [];
        }

        request()->attributes->set($key, $map);

        return $map;
    }
}
