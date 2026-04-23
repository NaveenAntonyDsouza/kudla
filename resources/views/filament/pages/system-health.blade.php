<x-filament-panels::page>
    {{-- Cache Management Buttons --}}
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; margin-bottom: 1.5rem;">
        <button wire:click="clearConfigCache" type="button"
            style="padding: 0.75rem 1rem; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; text-align: center;"
            onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
            Clear Config Cache
        </button>
        <button wire:click="clearViewCache" type="button"
            style="padding: 0.75rem 1rem; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; text-align: center;"
            onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
            Clear View Cache
        </button>
        <button wire:click="clearRouteCache" type="button"
            style="padding: 0.75rem 1rem; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; text-align: center;"
            onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
            Clear Route Cache
        </button>
        <button wire:click="clearAllCaches" type="button"
            style="padding: 0.75rem 1rem; background: #dc2626; color: white; border: none; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600; cursor: pointer; text-align: center;"
            onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
            Clear ALL Caches
        </button>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
        {{-- Left Column: System Info --}}
        <div>
            {{-- Server Environment --}}
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.25rem; margin-bottom: 1rem;">
                <h3 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">Server Environment</h3>
                <table style="width: 100%; font-size: 0.875rem;">
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 0.5rem 0; color: #6b7280; width: 40%;">PHP Version</td>
                        <td style="padding: 0.5rem 0; font-weight: 500;">{{ $systemInfo['php_version'] }}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 0.5rem 0; color: #6b7280;">Laravel Version</td>
                        <td style="padding: 0.5rem 0; font-weight: 500;">{{ $systemInfo['laravel_version'] }}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 0.5rem 0; color: #6b7280;">MySQL Version</td>
                        <td style="padding: 0.5rem 0; font-weight: 500;">{{ $systemInfo['mysql_version'] }}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 0.5rem 0; color: #6b7280;">App Environment</td>
                        <td style="padding: 0.5rem 0;">
                            <span style="padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; {{ $systemInfo['app_env'] === 'production' ? 'background: #dcfce7; color: #166534;' : 'background: #fef3c7; color: #92400e;' }}">
                                {{ $systemInfo['app_env'] }}
                            </span>
                        </td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 0.5rem 0; color: #6b7280;">Debug Mode</td>
                        <td style="padding: 0.5rem 0;">
                            <span style="padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; {{ $systemInfo['app_debug'] ? 'background: #fee2e2; color: #991b1b;' : 'background: #dcfce7; color: #166534;' }}">
                                {{ $systemInfo['app_debug'] ? 'ON' : 'OFF' }}
                            </span>
                        </td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 0.5rem 0; color: #6b7280;">Queue Driver</td>
                        <td style="padding: 0.5rem 0; font-weight: 500;">{{ $systemInfo['queue_driver'] }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem 0; color: #6b7280;">Mail Driver</td>
                        <td style="padding: 0.5rem 0; font-weight: 500;">{{ $systemInfo['mail_driver'] }}</td>
                    </tr>
                </table>
            </div>

            {{-- PHP Limits --}}
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.25rem; margin-bottom: 1rem;">
                <h3 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">PHP Limits</h3>
                <table style="width: 100%; font-size: 0.875rem;">
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 0.5rem 0; color: #6b7280; width: 40%;">Upload Max Filesize</td>
                        <td style="padding: 0.5rem 0; font-weight: 500;">{{ $systemInfo['max_upload'] }}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 0.5rem 0; color: #6b7280;">Post Max Size</td>
                        <td style="padding: 0.5rem 0; font-weight: 500;">{{ $systemInfo['max_post'] }}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 0.5rem 0; color: #6b7280;">Memory Limit</td>
                        <td style="padding: 0.5rem 0; font-weight: 500;">{{ $systemInfo['memory_limit'] }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem 0; color: #6b7280;">Max Execution Time</td>
                        <td style="padding: 0.5rem 0; font-weight: 500;">{{ $systemInfo['max_execution_time'] }}</td>
                    </tr>
                </table>
            </div>

            {{-- Disk Usage --}}
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.25rem; margin-bottom: 1rem;">
                <h3 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">Disk Usage</h3>
                <div style="margin-bottom: 0.75rem;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.875rem; margin-bottom: 0.25rem;">
                        <span style="color: #6b7280;">{{ $systemInfo['disk_free'] }} free of {{ $systemInfo['disk_total'] }}</span>
                        <span style="font-weight: 600; {{ $systemInfo['disk_used_percent'] > 90 ? 'color: #dc2626;' : ($systemInfo['disk_used_percent'] > 75 ? 'color: #d97706;' : 'color: #059669;') }}">{{ $systemInfo['disk_used_percent'] }}% used</span>
                    </div>
                    <div style="width: 100%; height: 0.5rem; background: #e5e7eb; border-radius: 9999px; overflow: hidden;">
                        <div style="height: 100%; border-radius: 9999px; width: {{ $systemInfo['disk_used_percent'] }}%; {{ $systemInfo['disk_used_percent'] > 90 ? 'background: #dc2626;' : ($systemInfo['disk_used_percent'] > 75 ? 'background: #d97706;' : 'background: #059669;') }}"></div>
                    </div>
                </div>
                <p style="font-size: 0.8rem; color: #9ca3af;">Storage (uploads): {{ $systemInfo['storage_size'] }}</p>
            </div>

            {{-- Cache Status --}}
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.25rem; margin-bottom: 1rem;">
                <h3 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">Cache Status</h3>
                <table style="width: 100%; font-size: 0.875rem;">
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 0.5rem 0; color: #6b7280; width: 40%;">Config Cache</td>
                        <td style="padding: 0.5rem 0;">
                            <span style="color: {{ $systemInfo['config_cached'] ? '#059669' : '#6b7280' }}; font-weight: 500;">{{ $systemInfo['config_cached'] ? 'Cached' : 'Not Cached' }}</span>
                        </td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 0.5rem 0; color: #6b7280;">Routes Cache</td>
                        <td style="padding: 0.5rem 0;">
                            <span style="color: {{ $systemInfo['routes_cached'] ? '#059669' : '#6b7280' }}; font-weight: 500;">{{ $systemInfo['routes_cached'] ? 'Cached' : 'Not Cached' }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem 0; color: #6b7280;">Views Cache</td>
                        <td style="padding: 0.5rem 0;">
                            <span style="color: {{ $systemInfo['views_cached'] ? '#059669' : '#6b7280' }}; font-weight: 500;">{{ $systemInfo['views_cached'] ? 'Cached' : 'Not Cached' }}</span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Right Column: Extensions + File Permissions --}}
        <div>
            {{-- PHP Extensions --}}
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.25rem; margin-bottom: 1rem;">
                <h3 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">PHP Extensions</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.25rem;">
                    @foreach($systemInfo['extensions'] as $ext => $loaded)
                        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.375rem 0; font-size: 0.875rem;">
                            @if($loaded)
                                <span style="color: #059669; font-weight: bold;">&#10003;</span>
                            @else
                                <span style="color: #dc2626; font-weight: bold;">&#10007;</span>
                            @endif
                            <span style="{{ $loaded ? 'color: #374151;' : 'color: #dc2626; font-weight: 500;' }}">{{ $ext }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- File Permissions --}}
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.25rem; margin-bottom: 1rem;">
                <h3 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">File Permissions</h3>
                @foreach($systemInfo['writable_paths'] as $path => $writable)
                    <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.375rem 0; font-size: 0.875rem;">
                        @if($writable)
                            <span style="color: #059669; font-weight: bold;">&#10003;</span>
                            <span style="color: #374151;">{{ $path }}</span>
                            <span style="color: #9ca3af; font-size: 0.75rem;">(writable)</span>
                        @else
                            <span style="color: #dc2626; font-weight: bold;">&#10007;</span>
                            <span style="color: #dc2626; font-weight: 500;">{{ $path }}</span>
                            <span style="color: #dc2626; font-size: 0.75rem;">(not writable!)</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Error Log Viewer (Full Width) --}}
    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.25rem; margin-top: 1rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="font-size: 1rem; font-weight: 600; color: #111827;">Recent Error Logs</h3>
            <div style="display: flex; gap: 0.5rem;">
                <button wire:click="refreshLogs" type="button"
                    style="padding: 0.375rem 0.75rem; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.8rem; cursor: pointer;">
                    Refresh
                </button>
                <button wire:click="clearLogs" type="button"
                    style="padding: 0.375rem 0.75rem; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; border-radius: 0.375rem; font-size: 0.8rem; cursor: pointer;">
                    Clear Log File
                </button>
            </div>
        </div>

        @if(empty($logEntries))
            <p style="color: #9ca3af; font-size: 0.875rem; text-align: center; padding: 2rem 0;">No log entries found. The log file is empty or doesn't exist.</p>
        @else
            <div style="max-height: 500px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
                @foreach($logEntries as $entry)
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #f3f4f6; font-size: 0.8rem; {{ $entry['level'] === 'error' ? 'background: #fef2f2;' : ($entry['level'] === 'warning' ? 'background: #fffbeb;' : '') }}">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                            <span style="font-size: 0.7rem; color: #9ca3af;">{{ $entry['timestamp'] }}</span>
                            <span style="padding: 0.0625rem 0.375rem; border-radius: 9999px; font-size: 0.65rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;
                                {{ $entry['level'] === 'error' ? 'background: #fee2e2; color: #991b1b;' : ($entry['level'] === 'warning' ? 'background: #fef3c7; color: #92400e;' : 'background: #e0e7ff; color: #3730a3;') }}">
                                {{ $entry['level'] }}
                            </span>
                        </div>
                        <pre style="margin: 0; white-space: pre-wrap; word-break: break-all; font-family: ui-monospace, monospace; font-size: 0.75rem; color: #374151; max-height: 100px; overflow-y: auto;">{{ \Illuminate\Support\Str::limit($entry['message'], 500) }}</pre>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
