<x-filament-panels::page>
    {{-- Stats Row --}}
    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 24px;">
        @foreach([
            ['label' => 'Total Users', 'value' => number_format($stats['total']), 'color' => '#8B1D91'],
            ['label' => 'Active', 'value' => number_format($stats['active']), 'color' => '#10B981'],
            ['label' => 'Inactive 30d+', 'value' => number_format($stats['inactive_30d']), 'color' => '#F59E0B'],
            ['label' => 'New This Month', 'value' => number_format($stats['new_this_month']), 'color' => '#3B82F6'],
            ['label' => 'Avg Completion', 'value' => $stats['avg_completion'] . '%', 'color' => '#6366F1'],
        ] as $stat)
        <div style="background: rgba(128,128,128,0.05); border: 1px solid rgba(128,128,128,0.2); border-radius: 8px; padding: 16px; text-align: center;">
            <div style="font-size: 24px; font-weight: 700; color: {{ $stat['color'] }};">{{ $stat['value'] }}</div>
            <div style="font-size: 12px; opacity: 0.6; margin-top: 4px;">{{ $stat['label'] }}</div>
        </div>
        @endforeach
    </div>

    {{-- Charts Row --}}
    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 16px; margin-bottom: 24px;">
        {{-- Registration Trend --}}
        <div style="background: rgba(128,128,128,0.05); border: 1px solid rgba(128,128,128,0.2); border-radius: 8px; padding: 16px;">
            <div style="font-size: 14px; font-weight: 600; margin-bottom: 12px;">Registration Trend (12 months)</div>
            <canvas id="regChart" height="200"></canvas>
        </div>

        {{-- Gender Split --}}
        <div style="background: rgba(128,128,128,0.05); border: 1px solid rgba(128,128,128,0.2); border-radius: 8px; padding: 16px;">
            <div style="font-size: 14px; font-weight: 600; margin-bottom: 12px;">Gender Distribution</div>
            <canvas id="genderChart" height="200"></canvas>
        </div>

        {{-- Religion Breakdown --}}
        <div style="background: rgba(128,128,128,0.05); border: 1px solid rgba(128,128,128,0.2); border-radius: 8px; padding: 16px;">
            <div style="font-size: 14px; font-weight: 600; margin-bottom: 12px;">Religion Distribution</div>
            <canvas id="religionChart" height="200"></canvas>
        </div>
    </div>

    {{-- Tables Row --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 24px;">
        {{-- State Distribution --}}
        <div style="background: rgba(128,128,128,0.05); border: 1px solid rgba(128,128,128,0.2); border-radius: 8px; padding: 16px; max-height: 400px; overflow-y: auto;">
            <div style="font-size: 14px; font-weight: 600; margin-bottom: 12px;">State-wise Distribution</div>
            <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                <thead><tr style="border-bottom: 1px solid rgba(128,128,128,0.2);"><th style="text-align: left; padding: 6px;">State</th><th style="text-align: right; padding: 6px;">Count</th></tr></thead>
                <tbody>
                @foreach($stateData as $row)
                <tr style="border-bottom: 1px solid rgba(128,128,128,0.1);"><td style="padding: 6px;">{{ $row->state }}</td><td style="text-align: right; padding: 6px;">{{ number_format($row->total) }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{-- Inactive Users --}}
        <div style="background: rgba(128,128,128,0.05); border: 1px solid rgba(128,128,128,0.2); border-radius: 8px; padding: 16px; max-height: 400px; overflow-y: auto;">
            <div style="font-size: 14px; font-weight: 600; margin-bottom: 12px;">Inactive Users (30+ days)</div>
            <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                <thead><tr style="border-bottom: 1px solid rgba(128,128,128,0.2);"><th style="text-align: left; padding: 6px;">Matri ID</th><th style="text-align: left; padding: 6px;">Name</th><th style="text-align: right; padding: 6px;">Last Login</th></tr></thead>
                <tbody>
                @foreach($inactiveUsers as $u)
                <tr style="border-bottom: 1px solid rgba(128,128,128,0.1);"><td style="padding: 6px;">{{ $u['matri_id'] }}</td><td style="padding: 6px;">{{ $u['name'] }}</td><td style="text-align: right; padding: 6px;">{{ $u['last_login'] }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{-- Incomplete Profiles --}}
        <div style="background: rgba(128,128,128,0.05); border: 1px solid rgba(128,128,128,0.2); border-radius: 8px; padding: 16px; max-height: 400px; overflow-y: auto;">
            <div style="font-size: 14px; font-weight: 600; margin-bottom: 12px;">Incomplete Profiles (&lt;50%)</div>
            <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                <thead><tr style="border-bottom: 1px solid rgba(128,128,128,0.2);"><th style="text-align: left; padding: 6px;">Matri ID</th><th style="text-align: left; padding: 6px;">Name</th><th style="text-align: right; padding: 6px;">%</th></tr></thead>
                <tbody>
                @foreach($incompleteProfiles as $p)
                <tr style="border-bottom: 1px solid rgba(128,128,128,0.1);"><td style="padding: 6px;">{{ $p['matri_id'] }}</td><td style="padding: 6px;">{{ $p['name'] }}</td><td style="text-align: right; padding: 6px; color: #F59E0B;">{{ $p['completion'] }}%</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Export --}}
    <div style="margin-top: 16px;">
        <x-filament::button wire:click="exportCsv" icon="heroicon-o-arrow-down-tray">
            Export Users CSV
        </x-filament::button>
    </div>

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Registration trend
            new Chart(document.getElementById('regChart'), {
                type: 'line',
                data: {
                    labels: @json($registrationChart['labels'] ?? []),
                    datasets: [{ label: 'Registrations', data: @json($registrationChart['values'] ?? []), borderColor: '#8B1D91', backgroundColor: 'rgba(139,29,145,0.1)', fill: true, tension: 0.3 }]
                },
                options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
            });

            // Gender
            new Chart(document.getElementById('genderChart'), {
                type: 'doughnut',
                data: {
                    labels: @json(array_map('ucfirst', array_keys($genderData))),
                    datasets: [{ data: @json(array_values($genderData)), backgroundColor: ['#3B82F6', '#EC4899'] }]
                },
                options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
            });

            // Religion
            new Chart(document.getElementById('religionChart'), {
                type: 'doughnut',
                data: {
                    labels: @json(array_keys($religionData)),
                    datasets: [{ data: @json(array_values($religionData)), backgroundColor: ['#8B1D91', '#00BCD4', '#F59E0B', '#10B981', '#6366F1', '#EF4444'] }]
                },
                options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
            });
        });
    </script>
</x-filament-panels::page>
