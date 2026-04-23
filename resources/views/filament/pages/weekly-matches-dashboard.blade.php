<x-filament-panels::page>
    {{-- Status banner --}}
    @if($enabled)
        <div style="padding: 1rem 1.25rem; background: #d1fae5; color: #065f46; border-radius: 0.5rem; font-weight: 600; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
            <span style="width: 10px; height: 10px; background: #10b981; border-radius: 50%;"></span>
            Weekly matches ENABLED · Scheduled Sundays at 10:00 AM · {{ $match_count }} matches per email · min score {{ $min_score }}
        </div>
    @else
        <div style="padding: 1rem 1.25rem; background: #fee2e2; color: #991b1b; border-radius: 0.5rem; font-weight: 600; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
            <span style="width: 10px; height: 10px; background: #ef4444; border-radius: 50%;"></span>
            Weekly matches DISABLED · Enable via SiteSetting <code>weekly_matches_enabled</code>
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
            <p style="font-size: 0.875rem; color: #6b7280; margin: 0 0 1rem;">If the command ran right now:</p>

            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0.75rem; background: #f9fafb; border-radius: 0.375rem;">
                    <span style="color: #374151;">Eligible candidates</span>
                    <strong style="color: #111827;">{{ number_format($next_run['eligible'] ?? 0) }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0.75rem; background: #f9fafb; border-radius: 0.375rem;">
                    <span style="color: #374151;">Skipped — no matches found</span>
                    <strong style="color: #6b7280;">{{ number_format($next_run['skipped_no_matches'] ?? 0) }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0.75rem; background: #f9fafb; border-radius: 0.375rem;">
                    <span style="color: #374151;">Skipped — other (opt-out, rate-limit)</span>
                    <strong style="color: #6b7280;">{{ number_format($next_run['skipped_other'] ?? 0) }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.625rem 0.75rem; background: #ecfdf5; border-radius: 0.375rem; margin-top: 0.25rem;">
                    <span style="color: #065f46; font-weight: 600;">Would send</span>
                    <strong style="color: #064e3b; font-size: 1.125rem;">{{ number_format($next_run['sent'] ?? 0) }}</strong>
                </div>
            </div>
        </div>

        {{-- Eligibility funnel --}}
        <div style="padding: 1.25rem; background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
            <h3 style="font-weight: 600; font-size: 1rem; color: #111827; margin: 0 0 1rem;">Eligibility Funnel</h3>
            <p style="font-size: 0.875rem; color: #6b7280; margin: 0 0 1rem;">How many members qualify at each step:</p>

            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0.75rem; background: #eff6ff; border-radius: 0.375rem;">
                    <span style="color: #1e40af;">Active members</span>
                    <strong style="color: #1e3a8a;">{{ number_format($eligibility['members_total']) }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0.75rem; background: #dbeafe; border-radius: 0.375rem;">
                    <span style="color: #1e40af;">with Partner Preferences set</span>
                    <strong style="color: #1e3a8a;">{{ number_format($eligibility['with_preferences']) }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0.75rem; background: #bfdbfe; border-radius: 0.375rem;">
                    <span style="color: #1e40af;">logged in within 60 days</span>
                    <strong style="color: #1e3a8a;">{{ number_format($eligibility['active_last_60']) }}</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- Config footer --}}
    <div style="padding: 1rem 1.25rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem; color: #374151;">
        <strong>Configuration:</strong>
        Matches per email: <code>{{ $match_count }}</code> (<code>weekly_matches_count</code>) ·
        Min match score: <code>{{ $min_score }}</code> (<code>weekly_matches_min_score</code>) ·
        Enabled: <code>{{ $enabled ? 'true' : 'false' }}</code> (<code>weekly_matches_enabled</code>).
        Edit values in the <code>site_settings</code> table.
    </div>
</x-filament-panels::page>
