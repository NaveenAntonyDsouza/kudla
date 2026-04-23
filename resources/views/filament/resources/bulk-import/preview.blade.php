<x-filament-panels::page>
    {{-- Status banner --}}
    @php
        $statusColor = \App\Models\BulkImport::statusColors()[$record->status] ?? 'gray';
        $statusLabel = \App\Models\BulkImport::statusOptions()[$record->status] ?? $record->status;
        $colorMap = [
            'gray' => ['bg' => '#f3f4f6', 'text' => '#374151'],
            'info' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
            'warning' => ['bg' => '#fef3c7', 'text' => '#92400e'],
            'success' => ['bg' => '#d1fae5', 'text' => '#065f46'],
            'danger' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
        ];
        $c = $colorMap[$statusColor] ?? $colorMap['gray'];
    @endphp

    <div style="padding: 1rem 1.25rem; background: {{ $c['bg'] }}; color: {{ $c['text'] }}; border-radius: 0.5rem; font-weight: 600; margin-bottom: 1rem;">
        Status: {{ $statusLabel }}
        @if($record->completed_at)
            · Completed {{ $record->completed_at->diffForHumans() }}
        @endif
    </div>

    {{-- Summary cards --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <div style="padding: 1rem; background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; font-weight: 600;">Total Rows</div>
            <div style="font-size: 1.875rem; font-weight: 700; color: #111827;">{{ number_format($record->total_rows) }}</div>
        </div>
        <div style="padding: 1rem; background: #f0fdf4; border: 1px solid #86efac; border-radius: 0.5rem;">
            <div style="font-size: 0.75rem; color: #166534; text-transform: uppercase; font-weight: 600;">Valid</div>
            <div style="font-size: 1.875rem; font-weight: 700; color: #15803d;">{{ number_format($record->valid_rows) }}</div>
        </div>
        <div style="padding: 1rem; background: {{ $record->invalid_rows > 0 ? '#fef2f2' : 'white' }}; border: 1px solid {{ $record->invalid_rows > 0 ? '#fecaca' : '#e5e7eb' }}; border-radius: 0.5rem;">
            <div style="font-size: 0.75rem; color: {{ $record->invalid_rows > 0 ? '#991b1b' : '#6b7280' }}; text-transform: uppercase; font-weight: 600;">Invalid</div>
            <div style="font-size: 1.875rem; font-weight: 700; color: {{ $record->invalid_rows > 0 ? '#dc2626' : '#111827' }};">{{ number_format($record->invalid_rows) }}</div>
        </div>
        @if($record->imported_count > 0)
        <div style="padding: 1rem; background: #eff6ff; border: 1px solid #93c5fd; border-radius: 0.5rem;">
            <div style="font-size: 0.75rem; color: #1e40af; text-transform: uppercase; font-weight: 600;">Imported</div>
            <div style="font-size: 1.875rem; font-weight: 700; color: #2563eb;">{{ number_format($record->imported_count) }}</div>
        </div>
        @endif
    </div>

    {{-- Settings snapshot --}}
    <div style="padding: 1rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem; color: #374151;">
        <strong>Upload settings:</strong>
        Default Branch: <span style="color: #111827; font-weight: 600;">{{ $record->defaultBranch?->name ?? '—' }}</span>
        ·
        Send Welcome Email: <span style="color: #111827; font-weight: 600;">{{ ($record->settings['send_welcome_email'] ?? false) ? 'Yes' : 'No' }}</span>
        ·
        Uploaded by: <span style="color: #111827; font-weight: 600;">{{ $record->uploader?->name ?? '—' }}</span>
    </div>

    {{-- Errors summary (grouped by column) --}}
    @if(!empty($errorsSummary))
        <div style="margin-bottom: 1.5rem;">
            <h3 style="font-weight: 600; font-size: 1rem; color: #111827; margin-bottom: 0.5rem;">Error Summary (by column)</h3>
            <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 0.5rem; padding: 1rem;">
                @foreach($errorsSummary as $column => $errors)
                    <div style="margin-bottom: 0.75rem; padding-bottom: 0.75rem; border-bottom: 1px solid #fecaca;">
                        <div style="font-weight: 600; color: #991b1b; margin-bottom: 0.25rem;">
                            {{ $column }} <span style="font-weight: normal; color: #7f1d1d;">— {{ count($errors) }} row{{ count($errors) !== 1 ? 's' : '' }} affected</span>
                        </div>
                        <div style="font-size: 0.8125rem; color: #7f1d1d;">
                            @foreach(array_slice($errors, 0, 3) as $e)
                                <div>· Row {{ $e['row'] }}: {{ $e['message'] }}</div>
                            @endforeach
                            @if(count($errors) > 3)
                                <div style="font-style: italic;">+ {{ count($errors) - 3 }} more</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Row-level errors (first 50 rows with errors) --}}
    @if(!empty($validationErrors))
        <div style="margin-bottom: 1.5rem;">
            <h3 style="font-weight: 600; font-size: 1rem; color: #111827; margin-bottom: 0.5rem;">Invalid Rows Detail (first 50)</h3>
            <div style="max-height: 400px; overflow-y: auto; background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                    <thead style="background: #f9fafb; position: sticky; top: 0;">
                        <tr>
                            <th style="padding: 0.5rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Row</th>
                            <th style="padding: 0.5rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Errors</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice($validationErrors, 0, 50, true) as $rowNum => $errors)
                            <tr style="border-bottom: 1px solid #f3f4f6;">
                                <td style="padding: 0.5rem; vertical-align: top; font-weight: 600; color: #991b1b;">{{ $rowNum }}</td>
                                <td style="padding: 0.5rem; color: #374151;">
                                    @foreach($errors as $col => $msg)
                                        <div><strong>{{ $col }}:</strong> {{ $msg }}</div>
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if(count($validationErrors) > 50)
                <p style="font-size: 0.8125rem; color: #6b7280; margin-top: 0.5rem;">Showing 50 of {{ count($validationErrors) }} rows with errors. Download the full errors CSV via the button above.</p>
            @endif
        </div>
    @endif

    {{-- If everything is valid --}}
    @if($record->valid_rows > 0 && empty($validationErrors) && $record->status === 'validated')
        <div style="padding: 1rem; background: #f0fdf4; border: 1px solid #86efac; border-radius: 0.5rem;">
            <strong style="color: #15803d;">✓ All rows are valid.</strong>
            <span style="color: #166534;">Click "Approve &amp; Import" above to create the profiles.</span>
        </div>
    @endif
</x-filament-panels::page>
