<x-filament-panels::page>
    {{-- Status banner --}}
    @if($enabled)
        <div style="padding: 1rem 1.25rem; background: #d1fae5; color: #065f46; border-radius: 0.5rem; font-weight: 600; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
            <span style="width: 10px; height: 10px; background: #10b981; border-radius: 50%;"></span>
            Re-engagement is ENABLED · Scheduled daily at 9:00 AM
        </div>
    @else
        <div style="padding: 1rem 1.25rem; background: #fee2e2; color: #991b1b; border-radius: 0.5rem; font-weight: 600; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
            <span style="width: 10px; height: 10px; background: #ef4444; border-radius: 50%;"></span>
            Re-engagement is DISABLED · Enable via SiteSetting <code>reengagement_enabled</code>
        </div>
    @endif

    {{-- Stats summary --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <div style="padding: 1.25rem; background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; font-weight: 600;">Sent Today</div>
            <div style="font-size: 2rem; font-weight: 700; color: #111827;">{{ number_format($stats['sent_today']) }}</div>
        </div>
        <div style="padding: 1.25rem; background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; font-weight: 600;">This Week</div>
            <div style="font-size: 2rem; font-weight: 700; color: #111827;">{{ number_format($stats['sent_this_week']) }}</div>
        </div>
        <div style="padding: 1.25rem; background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; font-weight: 600;">This Month</div>
            <div style="font-size: 2rem; font-weight: 700; color: #111827;">{{ number_format($stats['sent_this_month']) }}</div>
        </div>
        <div style="padding: 1.25rem; background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; font-weight: 600;">All Time</div>
            <div style="font-size: 2rem; font-weight: 700; color: #111827;">{{ number_format($stats['total_sent_ever']) }}</div>
        </div>
    </div>

    {{-- Two-column layout --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">

        {{-- Next run forecast --}}
        <div style="padding: 1.25rem; background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
            <h3 style="font-weight: 600; font-size: 1rem; color: #111827; margin: 0 0 1rem;">Next Scheduled Run — Forecast</h3>
            <p style="font-size: 0.875rem; color: #6b7280; margin: 0 0 1rem;">If the command ran right now, it would send:</p>

            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0.75rem; background: #f9fafb; border-radius: 0.375rem;">
                    <span style="color: #374151;">Level 1 ({{ $thresholds[1] }}+ days)</span>
                    <strong style="color: #111827;">{{ $next_run['sent_by_level'][1] ?? 0 }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0.75rem; background: #f9fafb; border-radius: 0.375rem;">
                    <span style="color: #374151;">Level 2 ({{ $thresholds[2] }}+ days)</span>
                    <strong style="color: #111827;">{{ $next_run['sent_by_level'][2] ?? 0 }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0.75rem; background: #f9fafb; border-radius: 0.375rem;">
                    <span style="color: #374151;">Level 3 ({{ $thresholds[3] }}+ days)</span>
                    <strong style="color: #111827;">{{ $next_run['sent_by_level'][3] ?? 0 }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.625rem 0.75rem; background: #ecfdf5; border-radius: 0.375rem; margin-top: 0.25rem;">
                    <span style="color: #065f46; font-weight: 600;">Total to send</span>
                    <strong style="color: #064e3b; font-size: 1.125rem;">{{ array_sum($next_run['sent_by_level'] ?? []) }}</strong>
                </div>
            </div>
        </div>

        {{-- Inactive member breakdown --}}
        <div style="padding: 1.25rem; background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
            <h3 style="font-weight: 600; font-size: 1rem; color: #111827; margin: 0 0 1rem;">Inactive Members</h3>
            <p style="font-size: 0.875rem; color: #6b7280; margin: 0 0 1rem;">Active non-staff members by last-login age:</p>

            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0.75rem; background: #fef3c7; border-radius: 0.375rem;">
                    <span style="color: #92400e;">7+ days inactive</span>
                    <strong style="color: #78350f;">{{ number_format($inactive_breakdown['7+']) }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0.75rem; background: #fed7aa; border-radius: 0.375rem;">
                    <span style="color: #9a3412;">14+ days inactive</span>
                    <strong style="color: #7c2d12;">{{ number_format($inactive_breakdown['14+']) }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0.75rem; background: #fecaca; border-radius: 0.375rem;">
                    <span style="color: #991b1b;">30+ days inactive</span>
                    <strong style="color: #7f1d1d;">{{ number_format($inactive_breakdown['30+']) }}</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- Level distribution --}}
    <div style="padding: 1.25rem; background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem; margin-bottom: 1.5rem;">
        <h3 style="font-weight: 600; font-size: 1rem; color: #111827; margin: 0 0 1rem;">Users by Current Re-engagement Level</h3>
        <p style="font-size: 0.875rem; color: #6b7280; margin: 0 0 1rem;">Level resets to 0 when the user logs in.</p>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
            <div style="padding: 1rem; background: #fef3c7; border-radius: 0.5rem; text-align: center;">
                <div style="font-size: 0.75rem; color: #92400e; text-transform: uppercase; font-weight: 600;">Level 1 (7-day)</div>
                <div style="font-size: 1.75rem; font-weight: 700; color: #78350f;">{{ number_format($levels[1]) }}</div>
            </div>
            <div style="padding: 1rem; background: #fed7aa; border-radius: 0.5rem; text-align: center;">
                <div style="font-size: 0.75rem; color: #9a3412; text-transform: uppercase; font-weight: 600;">Level 2 (14-day)</div>
                <div style="font-size: 1.75rem; font-weight: 700; color: #7c2d12;">{{ number_format($levels[2]) }}</div>
            </div>
            <div style="padding: 1rem; background: #fecaca; border-radius: 0.5rem; text-align: center;">
                <div style="font-size: 0.75rem; color: #991b1b; text-transform: uppercase; font-weight: 600;">Level 3 (30-day)</div>
                <div style="font-size: 1.75rem; font-weight: 700; color: #7f1d1d;">{{ number_format($levels[3]) }}</div>
            </div>
        </div>
    </div>

    {{-- Config info --}}
    <div style="padding: 1rem 1.25rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem; color: #374151;">
        <strong>Thresholds:</strong>
        Level 1 at {{ $thresholds[1] }} days ·
        Level 2 at {{ $thresholds[2] }} days ·
        Level 3 at {{ $thresholds[3] }} days.
        Edit in <code>site_settings</code> table (keys: <code>reengagement_threshold_days_1/2/3</code>).
    </div>
</x-filament-panels::page>
