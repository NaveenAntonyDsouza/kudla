<x-filament-panels::page>
    {{-- Stats Row --}}
    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 24px;">
        @foreach([
            ['label' => 'Total Interests', 'value' => number_format($stats['total_interests']), 'color' => '#8B1D91'],
            ['label' => 'Acceptance Rate', 'value' => $stats['acceptance_rate'] . '%', 'color' => '#10B981'],
            ['label' => 'Pending', 'value' => number_format($stats['pending_interests']), 'color' => '#F59E0B'],
            ['label' => 'Profile Views', 'value' => number_format($stats['total_views']), 'color' => '#3B82F6'],
            ['label' => 'Shortlists', 'value' => number_format($stats['total_shortlists']), 'color' => '#6366F1'],
        ] as $stat)
        <div style="background: rgba(128,128,128,0.05); border: 1px solid rgba(128,128,128,0.2); border-radius: 8px; padding: 16px; text-align: center;">
            <div style="font-size: 24px; font-weight: 700; color: {{ $stat['color'] }};">{{ $stat['value'] }}</div>
            <div style="font-size: 12px; opacity: 0.6; margin-top: 4px;">{{ $stat['label'] }}</div>
        </div>
        @endforeach
    </div>

    {{-- Charts Row --}}
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 24px;">
        <div style="background: rgba(128,128,128,0.05); border: 1px solid rgba(128,128,128,0.2); border-radius: 8px; padding: 16px;">
            <div style="font-size: 14px; font-weight: 600; margin-bottom: 12px;">Interest Trend (12 months)</div>
            <canvas id="interestChart" height="200"></canvas>
        </div>
        <div style="background: rgba(128,128,128,0.05); border: 1px solid rgba(128,128,128,0.2); border-radius: 8px; padding: 16px;">
            <div style="font-size: 14px; font-weight: 600; margin-bottom: 12px;">Interest Status Breakdown</div>
            <canvas id="statusChart" height="200"></canvas>
        </div>
    </div>

    {{-- Tables Row --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 24px;">
        {{-- Most Viewed --}}
        <div style="background: rgba(128,128,128,0.05); border: 1px solid rgba(128,128,128,0.2); border-radius: 8px; padding: 16px; max-height: 400px; overflow-y: auto;">
            <div style="font-size: 14px; font-weight: 600; margin-bottom: 12px;">Most Viewed Profiles</div>
            <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                <thead><tr style="border-bottom: 1px solid rgba(128,128,128,0.2);"><th style="text-align: left; padding: 6px;">Profile</th><th style="text-align: right; padding: 6px;">Views</th></tr></thead>
                <tbody>
                @forelse($mostViewed as $item)
                <tr style="border-bottom: 1px solid rgba(128,128,128,0.1);"><td style="padding: 6px;">{{ $item['matri_id'] }}<br><span style="font-size: 11px; opacity: 0.6;">{{ $item['name'] }}</span></td><td style="text-align: right; padding: 6px; font-weight: 600;">{{ number_format($item['count']) }}</td></tr>
                @empty
                <tr><td colspan="2" style="padding: 12px; text-align: center; opacity: 0.5;">No data yet</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Most Shortlisted --}}
        <div style="background: rgba(128,128,128,0.05); border: 1px solid rgba(128,128,128,0.2); border-radius: 8px; padding: 16px; max-height: 400px; overflow-y: auto;">
            <div style="font-size: 14px; font-weight: 600; margin-bottom: 12px;">Most Shortlisted Profiles</div>
            <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                <thead><tr style="border-bottom: 1px solid rgba(128,128,128,0.2);"><th style="text-align: left; padding: 6px;">Profile</th><th style="text-align: right; padding: 6px;">Shortlists</th></tr></thead>
                <tbody>
                @forelse($mostShortlisted as $item)
                <tr style="border-bottom: 1px solid rgba(128,128,128,0.1);"><td style="padding: 6px;">{{ $item['matri_id'] }}<br><span style="font-size: 11px; opacity: 0.6;">{{ $item['name'] }}</span></td><td style="text-align: right; padding: 6px; font-weight: 600;">{{ number_format($item['count']) }}</td></tr>
                @empty
                <tr><td colspan="2" style="padding: 12px; text-align: center; opacity: 0.5;">No data yet</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Most Active Senders --}}
        <div style="background: rgba(128,128,128,0.05); border: 1px solid rgba(128,128,128,0.2); border-radius: 8px; padding: 16px; max-height: 400px; overflow-y: auto;">
            <div style="font-size: 14px; font-weight: 600; margin-bottom: 12px;">Most Active Interest Senders</div>
            <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                <thead><tr style="border-bottom: 1px solid rgba(128,128,128,0.2);"><th style="text-align: left; padding: 6px;">Profile</th><th style="text-align: right; padding: 6px;">Sent</th></tr></thead>
                <tbody>
                @forelse($mostActiveSenders as $item)
                <tr style="border-bottom: 1px solid rgba(128,128,128,0.1);"><td style="padding: 6px;">{{ $item['matri_id'] }}<br><span style="font-size: 11px; opacity: 0.6;">{{ $item['name'] }}</span></td><td style="text-align: right; padding: 6px; font-weight: 600;">{{ number_format($item['count']) }}</td></tr>
                @empty
                <tr><td colspan="2" style="padding: 12px; text-align: center; opacity: 0.5;">No data yet</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Export --}}
    <div style="margin-top: 16px;">
        <x-filament::button wire:click="exportCsv" icon="heroicon-o-arrow-down-tray">
            Export Interests CSV
        </x-filament::button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new Chart(document.getElementById('interestChart'), {
                type: 'line',
                data: {
                    labels: @json($interestChart['labels'] ?? []),
                    datasets: [{ label: 'Interests Sent', data: @json($interestChart['values'] ?? []), borderColor: '#EC4899', backgroundColor: 'rgba(236,72,153,0.1)', fill: true, tension: 0.3 }]
                },
                options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
            });

            const statusColors = { pending: '#F59E0B', accepted: '#10B981', declined: '#EF4444', cancelled: '#9CA3AF', expired: '#6B7280' };
            const statusData = @json($statusData);
            new Chart(document.getElementById('statusChart'), {
                type: 'doughnut',
                data: {
                    labels: Object.keys(statusData).map(s => s.charAt(0).toUpperCase() + s.slice(1)),
                    datasets: [{ data: Object.values(statusData), backgroundColor: Object.keys(statusData).map(s => statusColors[s] || '#9CA3AF') }]
                },
                options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
            });
        });
    </script>
</x-filament-panels::page>
