@php
    // Color helper for progress bars
    $color = function (float $pct): array {
        if ($pct >= 80) return ['bg' => '#10B981', 'light' => '#D1FAE5', 'text' => '#065F46'];
        if ($pct >= 50) return ['bg' => '#F59E0B', 'light' => '#FEF3C7', 'text' => '#92400E'];
        return ['bg' => '#EF4444', 'light' => '#FEE2E2', 'text' => '#991B1B'];
    };
@endphp

<x-filament-widgets::widget>
    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.25rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
            <h3 style="font-size: 1rem; font-weight: 600; color: #111827;">This Month's Target</h3>
            <span style="font-size: 0.8rem; color: #6b7280; background: #f3f4f6; padding: 0.25rem 0.625rem; border-radius: 9999px; font-weight: 500;">
                {{ $monthLabel }}
            </span>
        </div>

        @if(!$hasTarget)
            <div style="text-align: center; padding: 1.5rem; background: #f9fafb; border-radius: 0.5rem;">
                <svg style="width: 2.5rem; height: 2.5rem; color: #9ca3af; margin: 0 auto 0.5rem;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p style="font-size: 0.875rem; color: #6b7280; font-weight: 500; margin-bottom: 0.25rem;">No target set for this month</p>
                <p style="font-size: 0.75rem; color: #9ca3af;">Ask your admin to set a monthly target to track your progress and incentives.</p>
            </div>
        @else
            {{-- Progress Bars --}}
            <div style="display: grid; grid-template-columns: 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                {{-- Registrations --}}
                @php $regColor = $color($progress['registrations']['percent']); @endphp
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.375rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <svg style="width: 1rem; height: 1rem; color: #6b7280;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                            <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">Registrations</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 0.875rem; color: #6b7280;">{{ $progress['registrations']['actual'] }} / {{ $progress['registrations']['target'] }}</span>
                            <span style="font-size: 0.75rem; padding: 0.125rem 0.5rem; background: {{ $regColor['light'] }}; color: {{ $regColor['text'] }}; border-radius: 9999px; font-weight: 600;">
                                {{ $progress['registrations']['percent'] }}%
                            </span>
                        </div>
                    </div>
                    <div style="width: 100%; height: 0.5rem; background: #f3f4f6; border-radius: 9999px; overflow: hidden;">
                        <div style="height: 100%; background: {{ $regColor['bg'] }}; border-radius: 9999px; width: {{ $progress['registrations']['percent'] }}%; transition: width 0.3s;"></div>
                    </div>
                </div>

                {{-- Revenue --}}
                @php $revColor = $color($progress['revenue']['percent']); @endphp
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.375rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <svg style="width: 1rem; height: 1rem; color: #6b7280;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">Revenue</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 0.875rem; color: #6b7280;">₹{{ number_format($progress['revenue']['actual_rupees']) }} / ₹{{ number_format($progress['revenue']['target_rupees']) }}</span>
                            <span style="font-size: 0.75rem; padding: 0.125rem 0.5rem; background: {{ $revColor['light'] }}; color: {{ $revColor['text'] }}; border-radius: 9999px; font-weight: 600;">
                                {{ $progress['revenue']['percent'] }}%
                            </span>
                        </div>
                    </div>
                    <div style="width: 100%; height: 0.5rem; background: #f3f4f6; border-radius: 9999px; overflow: hidden;">
                        <div style="height: 100%; background: {{ $revColor['bg'] }}; border-radius: 9999px; width: {{ $progress['revenue']['percent'] }}%; transition: width 0.3s;"></div>
                    </div>
                </div>

                {{-- Calls --}}
                @php $callColor = $color($progress['calls']['percent']); @endphp
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.375rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <svg style="width: 1rem; height: 1rem; color: #6b7280;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">Calls</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 0.875rem; color: #6b7280;">{{ $progress['calls']['actual'] }} / {{ $progress['calls']['target'] }}</span>
                            <span style="font-size: 0.75rem; padding: 0.125rem 0.5rem; background: {{ $callColor['light'] }}; color: {{ $callColor['text'] }}; border-radius: 9999px; font-weight: 600;">
                                {{ $progress['calls']['percent'] }}%
                            </span>
                        </div>
                    </div>
                    <div style="width: 100%; height: 0.5rem; background: #f3f4f6; border-radius: 9999px; overflow: hidden;">
                        <div style="height: 100%; background: {{ $callColor['bg'] }}; border-radius: 9999px; width: {{ $progress['calls']['percent'] }}%; transition: width 0.3s;"></div>
                    </div>
                </div>
            </div>

            {{-- Incentive Earned --}}
            <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: 1px solid #f59e0b; border-radius: 0.75rem; padding: 1rem; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <div style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: #92400e; font-weight: 600; margin-bottom: 0.25rem;">
                        Incentive Earned This Month
                    </div>
                    <div style="font-size: 1.75rem; font-weight: 700; color: #78350f;">
                        ₹{{ number_format($incentive['total_rupees']) }}
                    </div>
                    <div style="font-size: 0.75rem; color: #92400e; margin-top: 0.25rem;">
                        Registration bonus: ₹{{ number_format($incentive['from_registrations_rupees']) }}
                        &nbsp;•&nbsp;
                        Revenue share: ₹{{ number_format($incentive['from_revenue_rupees']) }}
                    </div>
                </div>
                <div style="flex-shrink: 0;">
                    <svg style="width: 3rem; height: 3rem; color: #d97706;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.006 0a4.125 4.125 0 01-4.125-4.125V6.75a4.125 4.125 0 018.25 0v3.375a4.125 4.125 0 01-4.125 4.125z"/>
                    </svg>
                </div>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
