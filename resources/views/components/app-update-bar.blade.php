{{-- "A new version of the app is available" (App\Support\AppUpdate, owner 20 Nov 2026).
     23 Nov 2026 redesign: a light card under the header (the old plum bar merged into the plum home page
     hero), the app icon, one reason to update, Update + Later. Inline styles: the live CSS build is not
     rebuilt on deploy. "Later" hides it for 3 days. --}}
@if(\App\Support\AppUpdate::needed(request()))
    @php
        $__icon = \App\Models\SiteSetting::getValue('apple_touch_icon_url', '') ?: \App\Models\SiteSetting::getValue('favicon_url', '');
    @endphp
    <div id="app-update-bar" role="status" style="background: var(--color-primary-light, #F5E7F5); border-bottom: 1px solid rgba(0,0,0,.06);">
        <div style="max-width: 72rem; margin: 0 auto; padding: 10px 14px; display: flex; align-items: center; gap: 10px;">
            @if($__icon)
                <img src="{{ $__icon }}" alt="" width="34" height="34" style="flex: none; width: 34px; height: 34px; border-radius: 9px;">
            @endif
            <div style="flex: 1; min-width: 0; line-height: 1.3;">
                <div style="font-size: 14px; font-weight: 700; color: var(--color-primary, #64026A);">App update ready</div>
                <div style="font-size: 12.5px; color: #4b5563;">Get alerts for new interests and replies.</div>
            </div>
            <a href="{{ \App\Support\AppUpdate::playUrl() }}" style="flex: none; display: inline-flex; align-items: center; padding: 8px 14px; border-radius: 9999px; background: var(--color-primary, #64026A); color: #fff; font-size: 14px; font-weight: 700; text-decoration: none;">Update</a>
            <button type="button" aria-label="Remind me later" style="flex: none; background: none; border: 0; padding: 4px; font-size: 13px; color: #6b7280; text-decoration: underline; cursor: pointer;"
                onclick="try { document.cookie = '{{ \App\Support\AppUpdate::LATER_COOKIE }}=1; max-age=259200; path=/; secure; samesite=lax'; } catch (e) {} document.getElementById('app-update-bar').remove();">Later</button>
        </div>
        @if(\App\Support\AppUpdate::versionOf(request()->userAgent()) === '')
            {{-- apps from before 1.0.4 open Play links inside the app, so say how to do it by hand --}}
            <p style="margin: 0; padding: 0 16px 10px; font-size: 12px; color: #4b5563; text-align: center;">If the button does not open the Play Store, open the Play Store app, search for "{{ config('app.name') }}" and tap Update.</p>
        @endif
    </div>
@endif
