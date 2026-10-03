<x-filament-panels::page>
    @php
        $box = 'background: rgba(128,128,128,0.05); border: 1px solid rgba(128,128,128,0.2); border-radius: 8px; padding: 16px;';
        $th = 'text-align: left; padding: 6px; font-weight: 600;';
        $thR = 'text-align: right; padding: 6px; font-weight: 600;';
        $td = 'padding: 6px;';
        $tdR = 'padding: 6px; text-align: right; font-variant-numeric: tabular-nums;';
        $row = 'border-bottom: 1px solid rgba(128,128,128,0.1);';
        $quotaColor = $report['quotaPercent'] >= 90 ? '#DC2626' : ($report['quotaPercent'] >= 70 ? '#F59E0B' : '#10B981');
    @endphp

    {{-- Period --}}
    <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 8px;">
        <label for="email-activity-days" style="font-size: 13px; opacity: 0.7;">Period</label>
        <select id="email-activity-days" wire:model.live="days" style="font-size: 13px; padding: 4px 28px 4px 8px; border-radius: 6px; border: 1px solid rgba(128,128,128,0.3); background: transparent;">
            <option value="1">Today</option>
            <option value="7">Last 7 days</option>
            <option value="30">Last 30 days</option>
        </select>
    </div>

    {{-- Totals --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px;">
        @foreach([
            ['label' => 'Emails sent', 'value' => number_format($report['totals']['sent']), 'color' => '#8B1D91'],
            ['label' => 'Failed attempts', 'value' => number_format($report['totals']['failed']), 'color' => $report['totals']['failed'] > 0 ? '#DC2626' : '#10B981'],
            ['label' => 'Unsubscribes', 'value' => number_format($report['totals']['unsubscribed']), 'color' => '#F59E0B'],
            ['label' => 'Sent in last 24 hours (mailbox limit ' . number_format($report['quota']) . ')', 'value' => number_format($report['last24h']) . ' · ' . $report['quotaPercent'] . '%', 'color' => $quotaColor],
        ] as $stat)
            <div style="{{ $box }} text-align: center;">
                <div style="font-size: 24px; font-weight: 700; color: {{ $stat['color'] }};">{{ $stat['value'] }}</div>
                <div style="font-size: 12px; opacity: 0.6; margin-top: 4px;">{{ $stat['label'] }}</div>
            </div>
        @endforeach
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px; margin-bottom: 24px;">
        {{-- By email type --}}
        <div style="{{ $box }}">
            <div style="font-size: 14px; font-weight: 600; margin-bottom: 12px;">By email</div>
            <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                <thead><tr style="border-bottom: 1px solid rgba(128,128,128,0.2);"><th style="{{ $th }}">Email</th><th style="{{ $thR }}">Sent</th><th style="{{ $thR }}">Failed</th></tr></thead>
                <tbody>
                @forelse($report['byType'] as $item)
                    <tr style="{{ $row }}"><td style="{{ $td }}">{{ $item['type'] }}</td><td style="{{ $tdR }}">{{ number_format($item['sent']) }}</td><td style="{{ $tdR }} {{ $item['failed'] ? 'color: #DC2626; font-weight: 600;' : 'opacity: 0.5;' }}">{{ number_format($item['failed']) }}</td></tr>
                @empty
                    <tr><td colspan="3" style="padding: 12px; text-align: center; opacity: 0.5;">No emails in this period</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- By day --}}
        <div style="{{ $box }}">
            <div style="font-size: 14px; font-weight: 600; margin-bottom: 12px;">By day</div>
            <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                <thead><tr style="border-bottom: 1px solid rgba(128,128,128,0.2);"><th style="{{ $th }}">Day</th><th style="{{ $thR }}">Sent</th><th style="{{ $thR }}">Failed</th></tr></thead>
                <tbody>
                @foreach($report['daily'] as $day)
                    <tr style="{{ $row }}"><td style="{{ $td }}">{{ $day['date'] }}</td><td style="{{ $tdR }}">{{ number_format($day['sent']) }}</td><td style="{{ $tdR }} {{ $day['failed'] ? 'color: #DC2626; font-weight: 600;' : 'opacity: 0.5;' }}">{{ number_format($day['failed']) }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{-- Unsubscribes --}}
        <div style="{{ $box }}">
            <div style="font-size: 14px; font-weight: 600; margin-bottom: 12px;">Unsubscribes by email setting</div>
            <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                <tbody>
                @forelse($report['unsubscribes'] as $label => $count)
                    <tr style="{{ $row }}"><td style="{{ $td }}">{{ $label }}</td><td style="{{ $tdR }}">{{ number_format($count) }}</td></tr>
                @empty
                    <tr><td style="padding: 12px; text-align: center; opacity: 0.5;">No unsubscribes in this period</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent failures --}}
    <div style="{{ $box }}">
        <div style="font-size: 14px; font-weight: 600; margin-bottom: 12px;">Latest failed attempts</div>
        <div style="overflow-x: auto;">
            <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                <thead><tr style="border-bottom: 1px solid rgba(128,128,128,0.2);"><th style="{{ $th }}">When</th><th style="{{ $th }}">Email</th><th style="{{ $th }}">To</th><th style="{{ $th }}">Reason</th></tr></thead>
                <tbody>
                @forelse($report['failures'] as $failure)
                    <tr style="{{ $row }}"><td style="{{ $td }} white-space: nowrap;">{{ $failure['when'] }}</td><td style="{{ $td }}">{{ $failure['type'] }}</td><td style="{{ $td }}">{{ $failure['recipient'] }}</td><td style="{{ $td }} opacity: 0.8;">{{ \Illuminate\Support\Str::limit($failure['error'], 160) }}</td></tr>
                @empty
                    <tr><td colspan="4" style="padding: 12px; text-align: center; opacity: 0.5;">No failed attempts</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p style="font-size: 12px; opacity: 0.6; margin-top: 10px;">A queued email that fails is retried up to 3 times, so one email can appear here more than once. Records are kept for 180 days.</p>
    </div>
</x-filament-panels::page>
