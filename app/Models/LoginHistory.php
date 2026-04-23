<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginHistory extends Model
{
    protected $table = 'login_history';

    protected $fillable = [
        'user_id',
        'login_method',
        'ip_address',
        'user_agent',
        'logged_in_at',
    ];

    protected function casts(): array
    {
        return [
            'logged_in_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record a new login event.
     * Wrapped in try/catch so logging failures never break the login flow.
     */
    public static function record(User $user, string $method): void
    {
        try {
            static::create([
                'user_id' => $user->id,
                'login_method' => $method,
                'ip_address' => request()->ip(),
                'user_agent' => substr(request()->userAgent() ?? '', 0, 500),
                'logged_in_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Silently fail — logging must never break login
        }
    }

    /**
     * Parse device type from user agent string.
     */
    public function getDeviceTypeAttribute(): string
    {
        $ua = strtolower($this->user_agent ?? '');

        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            return 'Tablet';
        }
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'Mobile';
        }
        return 'Desktop';
    }

    /**
     * Parse browser name from user agent string.
     */
    public function getBrowserAttribute(): string
    {
        $ua = $this->user_agent ?? '';

        if (str_contains($ua, 'Edg/')) return 'Edge';
        if (str_contains($ua, 'OPR/') || str_contains($ua, 'Opera')) return 'Opera';
        if (str_contains($ua, 'Firefox/')) return 'Firefox';
        if (str_contains($ua, 'Chrome/') && !str_contains($ua, 'Edg/') && !str_contains($ua, 'OPR/')) return 'Chrome';
        if (str_contains($ua, 'Safari/') && !str_contains($ua, 'Chrome/')) return 'Safari';
        return 'Unknown';
    }

    /**
     * Parse operating system from user agent string.
     */
    public function getOsAttribute(): string
    {
        $ua = $this->user_agent ?? '';

        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') || str_contains($ua, 'iPod')) return 'iOS';
        if (str_contains($ua, 'Android')) return 'Android';
        if (str_contains($ua, 'Windows')) return 'Windows';
        if (str_contains($ua, 'Mac OS')) return 'macOS';
        if (str_contains($ua, 'Linux')) return 'Linux';
        return 'Unknown';
    }

    /**
     * Combined browser + OS label for display.
     */
    public function getDeviceLabelAttribute(): string
    {
        return $this->browser . ' / ' . $this->os;
    }
}
