<x-filament-panels::page>
    {{-- Header: Date range filter + Export --}}
    <div style="display: flex; align-items: flex-end; gap: 1rem; margin-bottom: 1.5rem;">
        <div style="flex: 0 0 260px;">
            <label for="dateRange" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Date Range</label>
            <select id="dateRange" wire:model.live="dateRange"
                style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; background: white;">
                @foreach($this->getDateRangeOptions() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div style="flex: 1;"></div>

        <button wire:click="exportCsv" type="button"
            style="padding: 0.5rem 1rem; background: #8B1D91; color: white; border: none; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;"
            onmouseover="this.style.background='#6B1571'" onmouseout="this.style.background='#8B1D91'">
            <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V3"/>
            </svg>
            Export CSV
        </button>
    </div>

    {{-- Totals Summary Cards --}}
    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 0.75rem; margin-bottom: 1.5rem;">
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem;">
            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Staff</div>
            <div style="font-size: 1.75rem; font-weight: 700; color: #111827; margin-top: 0.25rem;">{{ $totals['staff_count'] ?? 0 }}</div>
        </div>

        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem;">
            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Leads Assigned</div>
            <div style="font-size: 1.75rem; font-weight: 700; color: #111827; margin-top: 0.25rem;">{{ number_format($totals['leads_assigned'] ?? 0) }}</div>
        </div>

        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem;">
            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Converted</div>
            <div style="font-size: 1.75rem; font-weight: 700; color: #059669; margin-top: 0.25rem;">{{ number_format($totals['leads_converted'] ?? 0) }}</div>
            <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem;">{{ $totals['conversion_rate'] ?? 0 }}% rate</div>
        </div>

        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem;">
            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Calls Made</div>
            <div style="font-size: 1.75rem; font-weight: 700; color: #111827; margin-top: 0.25rem;">{{ number_format($totals['calls_made'] ?? 0) }}</div>
        </div>

        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem;">
            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Connected</div>
            <div style="font-size: 1.75rem; font-weight: 700; color: #111827; margin-top: 0.25rem;">{{ number_format($totals['calls_connected'] ?? 0) }}</div>
        </div>
    </div>

    {{-- Staff Performance Table --}}
    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; overflow: hidden;">
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb;">
            <h3 style="font-size: 1rem; font-weight: 600; color: #111827;">Staff Leaderboard (sorted by conversions)</h3>
        </div>

        @if(count($staffRows) === 0)
            <div style="padding: 2.5rem 1.25rem; text-align: center; color: #9ca3af;">
                <p>No staff members found.</p>
            </div>
        @else
            <div style="overflow-x: auto;">
                <table style="width: 100%; font-size: 0.875rem; border-collapse: collapse;">
                    <thead style="background: #f9fafb;">
                        <tr style="text-align: left;">
                            <th style="padding: 0.75rem 1rem; font-weight: 600; color: #6b7280; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em;">Staff</th>
                            <th style="padding: 0.75rem 1rem; font-weight: 600; color: #6b7280; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em;">Role</th>
                            <th style="padding: 0.75rem 1rem; font-weight: 600; color: #6b7280; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em; text-align: right;">Assigned</th>
                            <th style="padding: 0.75rem 1rem; font-weight: 600; color: #6b7280; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em; text-align: right;">Converted</th>
                            <th style="padding: 0.75rem 1rem; font-weight: 600; color: #6b7280; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em; text-align: right;">Rate</th>
                            <th style="padding: 0.75rem 1rem; font-weight: 600; color: #6b7280; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em; text-align: right;">Calls</th>
                            <th style="padding: 0.75rem 1rem; font-weight: 600; color: #6b7280; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em; text-align: right;">Connected</th>
                            <th style="padding: 0.75rem 1rem; font-weight: 600; color: #6b7280; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em; text-align: right;">Avg Dur.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($staffRows as $row)
                            <tr style="border-top: 1px solid #f3f4f6;">
                                <td style="padding: 0.75rem 1rem; font-weight: 500; color: #111827;">
                                    {{ $row['name'] }}
                                    <div style="font-size: 0.75rem; color: #9ca3af; font-weight: 400;">{{ $row['email'] }}</div>
                                </td>
                                <td style="padding: 0.75rem 1rem; color: #6b7280;">
                                    <span style="padding: 0.125rem 0.5rem; background: #e0e7ff; color: #3730a3; border-radius: 9999px; font-size: 0.7rem; font-weight: 600;">
                                        {{ $row['role'] }}
                                    </span>
                                </td>
                                <td style="padding: 0.75rem 1rem; text-align: right; color: #374151;">{{ number_format($row['leads_assigned']) }}</td>
                                <td style="padding: 0.75rem 1rem; text-align: right; color: #059669; font-weight: 600;">{{ number_format($row['leads_converted']) }}</td>
                                <td style="padding: 0.75rem 1rem; text-align: right;">
                                    <span style="color: {{ $row['conversion_rate'] >= 20 ? '#059669' : ($row['conversion_rate'] >= 10 ? '#d97706' : '#6b7280') }}; font-weight: 600;">
                                        {{ $row['conversion_rate'] }}%
                                    </span>
                                </td>
                                <td style="padding: 0.75rem 1rem; text-align: right; color: #374151;">{{ number_format($row['calls_made']) }}</td>
                                <td style="padding: 0.75rem 1rem; text-align: right; color: #374151;">{{ number_format($row['calls_connected']) }}</td>
                                <td style="padding: 0.75rem 1rem; text-align: right; color: #6b7280; font-size: 0.8rem;">
                                    {{ $row['avg_call_duration'] > 0 ? $row['avg_call_duration'] . ' min' : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-filament-panels::page>
