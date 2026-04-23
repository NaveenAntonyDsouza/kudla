<x-filament-panels::page>
    {{-- Driver status overview --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        @foreach($drivers as $driver)
            <div style="padding: 1.25rem; background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                    <div style="font-size: 0.875rem; font-weight: 600; color: #111827; text-transform: capitalize;">
                        {{ ucfirst($driver['key']) }}
                    </div>
                    @if($driver['configured'])
                        <span style="font-size: 0.7rem; padding: 0.2rem 0.5rem; background: #d1fae5; color: #065f46; border-radius: 9999px; font-weight: 600;">
                            ● Configured
                        </span>
                    @else
                        <span style="font-size: 0.7rem; padding: 0.2rem 0.5rem; background: #fef3c7; color: #92400e; border-radius: 9999px; font-weight: 600;">
                            ○ Not configured
                        </span>
                    @endif
                </div>
                <p style="font-size: 0.75rem; color: #6b7280; line-height: 1.4;">{{ $driver['label'] }}</p>
            </div>
        @endforeach
    </div>

    <form wire:submit="save">
        {{ $this->form }}

        <div style="margin-top: 1.5rem;">
            <x-filament::button type="submit" color="primary">
                Save Settings
            </x-filament::button>
        </div>
    </form>

    {{-- Help + env reference --}}
    <div style="margin-top: 2rem; padding: 1rem 1.25rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem; color: #374151;">
        <h3 style="font-weight: 600; font-size: 1rem; color: #111827; margin: 0 0 0.75rem;">How to configure cloud drivers</h3>

        <div style="margin-bottom: 1rem;">
            <div style="font-weight: 600; color: #111827; margin-bottom: 0.25rem;">Cloudinary</div>
            <p style="margin: 0 0 0.25rem; color: #6b7280;">Add to your <code>.env</code>:</p>
            <pre style="background: #1e293b; color: #e2e8f0; padding: 0.75rem; border-radius: 0.375rem; font-size: 0.75rem; overflow-x: auto;">CLOUDINARY_CLOUD_NAME=your-cloud-name
CLOUDINARY_API_KEY=your-api-key
CLOUDINARY_API_SECRET=your-api-secret</pre>
            <p style="margin: 0.5rem 0 0; color: #6b7280; font-size: 0.8rem;">Get credentials from <a href="https://cloudinary.com/console" target="_blank" style="color: #8b1d91;">Cloudinary dashboard</a>. Free tier: 25 GB storage.</p>
        </div>

        <div>
            <div style="font-weight: 600; color: #111827; margin-bottom: 0.25rem;">Cloudflare R2 (recommended for scale)</div>
            <p style="margin: 0 0 0.25rem; color: #6b7280;">Add to your <code>.env</code>:</p>
            <pre style="background: #1e293b; color: #e2e8f0; padding: 0.75rem; border-radius: 0.375rem; font-size: 0.75rem; overflow-x: auto;">R2_ACCESS_KEY_ID=your-access-key
R2_SECRET_ACCESS_KEY=your-secret-key
R2_BUCKET=your-bucket-name
R2_ENDPOINT=https://&lt;account-id&gt;.r2.cloudflarestorage.com
R2_PUBLIC_URL=https://pub-&lt;id&gt;.r2.dev</pre>
            <p style="margin: 0.5rem 0 0; color: #6b7280; font-size: 0.8rem;">Get credentials from <a href="https://dash.cloudflare.com" target="_blank" style="color: #8b1d91;">Cloudflare dashboard</a> → R2 → Manage API Tokens. Free tier: 10 GB storage + <strong>unlimited egress</strong>.</p>
        </div>
    </div>

    <div style="margin-top: 1rem; padding: 0.875rem 1.25rem; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 0.5rem; font-size: 0.8rem; color: #1e40af;">
        <strong>Hybrid mode:</strong> Changing the driver only affects new uploads. Existing photos keep serving from their original storage location — no migration needed. You can switch back at any time.
    </div>
</x-filament-panels::page>
