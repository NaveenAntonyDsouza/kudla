<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ThrottleRequests;

/**
 * "throttle:30,60" with its own counter per route (24 Nov 2026).
 *
 * Laravel's plain throttle keys its counter on the member (or, signed out, the IP) only, so every
 * throttled route shares ONE counter: a member who had polled chat, searched or refreshed badges
 * 10 times in a minute could no longer report a profile (10 a minute), and about 30 chat polls
 * after sending a message blocked sending for the rest of the hour. Here each route (method + path
 * pattern) counts on its own, which is what each limit in routes/api.php says it does.
 *
 * Used by routes/api.php as "throttle.route:max,minutes".
 */
class ThrottlePerRoute extends ThrottleRequests
{
    protected function resolveRequestSignature($request)
    {
        $route = $request->route();
        $scope = $route ? implode('|', $route->methods()) . ' ' . $route->uri() . '|' : '';

        return sha1($scope . parent::resolveRequestSignature($request));
    }
}
