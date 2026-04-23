<x-filament-panels::page>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
        {{-- Database Info --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.25rem;">
            <h3 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">Database Information</h3>
            <table style="width: 100%; font-size: 0.875rem;">
                <tr style="border-bottom: 1px solid #f3f4f6;">
                    <td style="padding: 0.5rem 0; color: #6b7280; width: 40%;">Database Name</td>
                    <td style="padding: 0.5rem 0; font-weight: 500;">{{ $databaseName }}</td>
                </tr>
                <tr style="border-bottom: 1px solid #f3f4f6;">
                    <td style="padding: 0.5rem 0; color: #6b7280;">Total Size</td>
                    <td style="padding: 0.5rem 0; font-weight: 500;">{{ $databaseSize }}</td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem 0; color: #6b7280;">Total Tables</td>
                    <td style="padding: 0.5rem 0; font-weight: 500;">{{ count($tables) }}</td>
                </tr>
            </table>
        </div>

        {{-- Download --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.25rem;">
            <h3 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">Download Backup</h3>
            <p style="font-size: 0.8rem; color: #6b7280; margin-bottom: 1rem;">
                Downloads a complete .sql backup of all tables and data. The backup uses pure PHP (no shell commands), so it works on shared hosting.
            </p>
            <button wire:click="downloadBackup" type="button"
                style="padding: 0.75rem 1.5rem; background: #059669; color: white; border: none; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;"
                onmouseover="this.style.background='#047857'" onmouseout="this.style.background='#059669'">
                <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V3"/></svg>
                Download .sql Backup
            </button>
        </div>
    </div>

    {{-- Tables List --}}
    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.25rem;">
        <h3 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">Tables ({{ count($tables) }})</h3>
        <div style="max-height: 400px; overflow-y: auto;">
            <table style="width: 100%; font-size: 0.8rem; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb; position: sticky; top: 0; background: white;">
                        <th style="text-align: left; padding: 0.5rem; color: #6b7280; font-weight: 600;">Table Name</th>
                        <th style="text-align: right; padding: 0.5rem; color: #6b7280; font-weight: 600;">Rows (approx)</th>
                        <th style="text-align: right; padding: 0.5rem; color: #6b7280; font-weight: 600;">Size</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tables as $table)
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 0.4rem 0.5rem; font-family: ui-monospace, monospace; color: #374151;">{{ $table['name'] }}</td>
                            <td style="padding: 0.4rem 0.5rem; text-align: right; color: #6b7280;">{{ number_format($table['rows']) }}</td>
                            <td style="padding: 0.4rem 0.5rem; text-align: right; color: #6b7280;">{{ $table['size'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
