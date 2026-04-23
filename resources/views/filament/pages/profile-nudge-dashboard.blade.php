<x-filament-panels::page>
    {{-- Status banner --}}
    @if($enabled)
        <div style="padding: 1rem 1.25rem; background: #d1fae5; color: #065f46; border-radius: 0.5rem; font-weight: 600; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
            <span style="width: 10px; height: 10px; background: #10b981; border-radius: 50%;"></span>
            Profile nudges ENABLED · Daily at 19:00 · Threshold {{ $threshold }}% · Max 4 nudges per user
        </div>
    @else
        <div style="padding: 1rem 1.25rem; background: #fee2e2; color: #991b1b; border-radius: 0.5rem; font-weight: 600; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
            <span style="width: 10px; height: 10px; background: #ef4444; border-radius: 50%;"></span>
            Profile nudges DISABLED · Enable via SiteSetting <code>profile_nudges_enabled</code>
        </div>
    @endif

    {{-- Stats --}}
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

    {{-- Two columns --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
        {{-- Next run forecast --}}
        <div style="padding: 1.25rem; background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
            <h3 style="font-weight: 600; font-size: 1rem; color: #111827; margin: 0 0 1rem;">Next Run — Forecast</h3>
            <p style="font-size: 0.875rem; color: #6b7280; margin: 0 0 1rem;">If daily command ran right now:</p>

            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0.75rem; background: #f9fafb; border-radius: 0.375rem;">
                    <span style="color: #374151;">Eligible candidates</span>
                    <strong style="color: #111827;">{{ number_format($next_run['eligible'] ?? 0) }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0.75rem; background: #f9fafb; border-radius: 0.375rem;">
                    <span style="color: #374151;">Skipped — profile complete</span>
                    <strong style="color: #6b7280;">{{ number_format($next_run['skipped_no_missing'] ?? 0) }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0.75rem; background: #f9fafb; border-radius: 0.375rem;">
                    <span style="color: #374151;">Skipped — rate/cap</span>
                    <strong style="color: #6b7280;">{{ number_format($next_run['skipped_other'] ?? 0) }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.625rem 0.75rem; background: #ecfdf5; border-radius: 0.375rem; margin-top: 0.25rem;">
                    <span style="color: #065f46; font-weight: 600;">Would send</span>
                    <strong style="color: #064e3b; font-size: 1.125rem;">{{ number_format($next_run['sent'] ?? 0) }}</strong>
                </div>
            </div>
        </div>

        {{-- By nudge type (last 30 days) --}}
        <div style="padding: 1.25rem; background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
            <h3 style="font-weight: 600; font-size: 1rem; color: #111827; margin: 0 0 1rem;">By Section (Last 30 Days)</h3>
            <p style="font-size: 0.875rem; color: #6b7280; margin: 0 0 1rem;">Which sections users are nudged about:</p>

            @if(empty($nudges_by_type))
                <p style="color: #9ca3af; font-style: italic;">No nudges sent in the last 30 days.</p>
            @else
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    @foreach($nudges_by_type as $type => $count)
                        <div style="display: flex; justify-content: space-between; padding: 0.5rem 0.75rem; background: #fef3c7; border-radius: 0.375rem;">
                            <span style="color: #92400e;">{{ str_replace('_', ' ', ucfirst($type)) }}</span>
                            <strong style="color: #78350f;">{{ number_format($count) }}</strong>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Lifetime distribution --}}
    <div style="padding: 1.25rem; background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem; margin-bottom: 1.5rem;">
        <h3 style="font-weight: 600; font-size: 1rem; color: #111827; margin: 0 0 1rem;">Users by Lifetime Nudges Received</h3>
        <p style="font-size: 0.875rem; color: #6b7280; margin: 0 0 1rem;">Cap: 4 per user lifetime.</p>

        <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 0.75rem;">
            <div style="padding: 0.75rem; background: #f9fafb; border-radius: 0.5rem; text-align: center;">
                <div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; font-weight: 600;">0 sent</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #111827;">{{ number_format($by_lifetime_count['0']) }}</div>
            </div>
            <div style="padding: 0.75rem; background: #fef3c7; border-radius: 0.5rem; text-align: center;">
                <div style="font-size: 0.7rem; color: #92400e; text-transform: uppercase; font-weight: 600;">1 sent</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #78350f;">{{ number_format($by_lifetime_count['1']) }}</div>
            </div>
            <div style="padding: 0.75rem; background: #fde68a; border-radius: 0.5rem; text-align: center;">
                <div style="font-size: 0.7rem; color: #92400e; text-transform: uppercase; font-weight: 600;">2 sent</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #78350f;">{{ number_format($by_lifetime_count['2']) }}</div>
            </div>
            <div style="padding: 0.75rem; background: #fed7aa; border-radius: 0.5rem; text-align: center;">
                <div style="font-size: 0.7rem; color: #9a3412; text-transform: uppercase; font-weight: 600;">3 sent</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #7c2d12;">{{ number_format($by_lifetime_count['3']) }}</div>
            </div>
            <div style="padding: 0.75rem; background: #fecaca; border-radius: 0.5rem; text-align: center;">
                <div style="font-size: 0.7rem; color: #991b1b; text-transform: uppercase; font-weight: 600;">4 (capped)</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #7f1d1d;">{{ number_format($by_lifetime_count['4']) }}</div>
            </div>
        </div>
    </div>

    {{-- Config info --}}
    <div style="padding: 1rem 1.25rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem; color: #374151;">
        <strong>Configuration:</strong>
        Threshold: <code>{{ $threshold }}%</code> (<code>profile_nudges_threshold_pct</code>) ·
        Enabled: <code>{{ $enabled ? 'true' : 'false' }}</code> (<code>profile_nudges_enabled</code>) ·
        Runs daily at 19:00. Rate limit: 7 days between nudges. Lifetime cap: 4. Deep-inactive users (30+ days) skipped — they're in the re-engagement flow.
    </div>
</x-filament-panels::page>
