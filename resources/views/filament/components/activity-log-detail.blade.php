<div style="font-size: 0.875rem; color: #374151;">
    <table style="width: 100%;">
        <tr style="border-bottom: 1px solid #f3f4f6;">
            <td style="padding: 0.5rem 0; color: #6b7280; width: 30%;">Admin</td>
            <td style="padding: 0.5rem 0; font-weight: 500;">{{ $record->admin?->name ?? 'Unknown' }}</td>
        </tr>
        <tr style="border-bottom: 1px solid #f3f4f6;">
            <td style="padding: 0.5rem 0; color: #6b7280;">Action</td>
            <td style="padding: 0.5rem 0; font-weight: 500;">{{ $record->action }}</td>
        </tr>
        @if($record->model_type)
        <tr style="border-bottom: 1px solid #f3f4f6;">
            <td style="padding: 0.5rem 0; color: #6b7280;">Target</td>
            <td style="padding: 0.5rem 0;">{{ $record->model_type }} #{{ $record->model_id }}</td>
        </tr>
        @endif
        <tr style="border-bottom: 1px solid #f3f4f6;">
            <td style="padding: 0.5rem 0; color: #6b7280;">IP Address</td>
            <td style="padding: 0.5rem 0;">{{ $record->ip_address ?? '—' }}</td>
        </tr>
        <tr style="border-bottom: 1px solid #f3f4f6;">
            <td style="padding: 0.5rem 0; color: #6b7280;">Time</td>
            <td style="padding: 0.5rem 0;">{{ $record->created_at->format('M j, Y g:i:s A') }}</td>
        </tr>
        @if($record->changes)
        <tr>
            <td style="padding: 0.5rem 0; color: #6b7280; vertical-align: top;">Details</td>
            <td style="padding: 0.5rem 0;">
                <pre style="margin: 0; font-size: 0.75rem; background: #f9fafb; padding: 0.5rem; border-radius: 0.375rem; white-space: pre-wrap; word-break: break-all;">{{ json_encode($record->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </td>
        </tr>
        @endif
    </table>
</div>
