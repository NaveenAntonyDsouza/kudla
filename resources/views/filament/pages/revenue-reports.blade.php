<x-filament-panels::page>
    {{-- Stats Row --}}
    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 24px;">
        @foreach([
            ['label' => 'Total Revenue', 'value' => '₹' . number_format($stats['total_revenue']), 'color' => '#10B981'],
            ['label' => 'This Month', 'value' => '₹' . number_format($stats['this_month']), 'color' => '#3B82F6'],
            ['label' => 'Active Subscriptions', 'value' => number_format($stats['active_subscriptions']), 'color' => '#8B1D91'],
            ['label' => 'Failed Payments', 'value' => number_format($stats['failed_payments']), 'color' => '#EF4444'],
            ['label' => 'Avg Revenue/User', 'value' => '₹' . number_format($stats['arpu']), 'color' => '#6366F1'],
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
            <div style="font-size: 14px; font-weight: 600; margin-bottom: 12px;">Monthly Revenue (12 months)</div>
            <canvas id="revenueChart" height="200"></canvas>
        </div>
        <div style="background: rgba(128,128,128,0.05); border: 1px solid rgba(128,128,128,0.2); border-radius: 8px; padding: 16px;">
            <div style="font-size: 14px; font-weight: 600; margin-bottom: 12px;">Revenue by Plan</div>
            <canvas id="planChart" height="200"></canvas>
        </div>
    </div>

    {{-- Tables --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
        {{-- Recent Payments --}}
        <div style="background: rgba(128,128,128,0.05); border: 1px solid rgba(128,128,128,0.2); border-radius: 8px; padding: 16px; max-height: 450px; overflow-y: auto;">
            <div style="font-size: 14px; font-weight: 600; margin-bottom: 12px;">Recent Payments</div>
            <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                <thead><tr style="border-bottom: 1px solid rgba(128,128,128,0.2);"><th style="text-align: left; padding: 6px;">User</th><th style="padding: 6px;">Plan</th><th style="text-align: right; padding: 6px;">Amount</th><th style="padding: 6px;">Status</th><th style="padding: 6px;">Date</th></tr></thead>
                <tbody>
                @forelse($recentPayments as $p)
                @php
                    $statusColor = match($p['status']) { 'paid' => '#10B981', 'pending' => '#F59E0B', 'failed' => '#EF4444', default => '#9CA3AF' };
                @endphp
                <tr style="border-bottom: 1px solid rgba(128,128,128,0.1);">
                    <td style="padding: 6px;">{{ $p['user'] }}<br><span style="font-size: 11px; opacity: 0.5;">{{ $p['email'] }}</span></td>
                    <td style="padding: 6px;">{{ $p['plan'] }}</td>
                    <td style="text-align: right; padding: 6px;">₹{{ $p['amount'] }}</td>
                    <td style="padding: 6px;"><span style="color: {{ $statusColor }}; font-weight: 600; font-size: 12px;">{{ ucfirst($p['status']) }}</span></td>
                    <td style="padding: 6px; font-size: 12px; opacity: 0.6;">{{ $p['date'] }}</td>
                </tr>
                @empty
                <tr><td colspan="5" style="padding: 12px; text-align: center; opacity: 0.5;">No payments yet</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div style="display: flex; flex-direction: column; gap: 16px;">
            {{-- Failed Payments --}}
            <div style="background: rgba(128,128,128,0.05); border: 1px solid rgba(128,128,128,0.2); border-radius: 8px; padding: 16px; max-height: 220px; overflow-y: auto;">
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 12px; color: #EF4444;">Failed Payments</div>
                <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                    <thead><tr style="border-bottom: 1px solid rgba(128,128,128,0.2);"><th style="text-align: left; padding: 6px;">User</th><th style="padding: 6px;">Plan</th><th style="text-align: right; padding: 6px;">Amount</th><th style="padding: 6px;">Date</th></tr></thead>
                    <tbody>
                    @forelse($failedPayments as $f)
                    <tr style="border-bottom: 1px solid rgba(128,128,128,0.1);"><td style="padding: 6px;">{{ $f['user'] }}</td><td style="padding: 6px;">{{ $f['plan'] }}</td><td style="text-align: right; padding: 6px;">₹{{ $f['amount'] }}</td><td style="padding: 6px; font-size: 12px;">{{ $f['date'] }}</td></tr>
                    @empty
                    <tr><td colspan="4" style="padding: 12px; text-align: center; opacity: 0.5;">No failed payments</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Expiring Subscriptions --}}
            <div style="background: rgba(128,128,128,0.05); border: 1px solid rgba(128,128,128,0.2); border-radius: 8px; padding: 16px; max-height: 220px; overflow-y: auto;">
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 12px; color: #F59E0B;">Expiring in 7 Days</div>
                <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                    <thead><tr style="border-bottom: 1px solid rgba(128,128,128,0.2);"><th style="text-align: left; padding: 6px;">Profile</th><th style="padding: 6px;">Plan</th><th style="text-align: right; padding: 6px;">Expires</th></tr></thead>
                    <tbody>
                    @forelse($expiringSubscriptions as $e)
                    <tr style="border-bottom: 1px solid rgba(128,128,128,0.1);"><td style="padding: 6px;">{{ $e['matri_id'] }}<br><span style="font-size: 11px; opacity: 0.5;">{{ $e['name'] }}</span></td><td style="padding: 6px;">{{ $e['plan'] }}</td><td style="text-align: right; padding: 6px; color: #F59E0B; font-weight: 600;">{{ $e['expires'] }}</td></tr>
                    @empty
                    <tr><td colspan="3" style="padding: 12px; text-align: center; opacity: 0.5;">No expiring subscriptions</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Export --}}
    <div style="margin-top: 16px;">
        <x-filament::button wire:click="exportCsv" icon="heroicon-o-arrow-down-tray">
            Export Payments CSV
        </x-filament::button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new Chart(document.getElementById('revenueChart'), {
                type: 'bar',
                data: {
                    labels: @json($revenueChart['labels'] ?? []),
                    datasets: [{ label: 'Revenue (₹)', data: @json($revenueChart['values'] ?? []), backgroundColor: 'rgba(16,185,129,0.7)', borderColor: '#10B981', borderWidth: 1 }]
                },
                options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
            });

            const planData = @json($planData);
            const planColors = ['#8B1D91', '#00BCD4', '#F59E0B', '#10B981', '#3B82F6', '#EF4444'];
            new Chart(document.getElementById('planChart'), {
                type: 'doughnut',
                data: {
                    labels: Object.keys(planData),
                    datasets: [{ data: Object.values(planData).map(v => Math.round(v)), backgroundColor: planColors.slice(0, Object.keys(planData).length) }]
                },
                options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
            });
        });
    </script>
</x-filament-panels::page>
