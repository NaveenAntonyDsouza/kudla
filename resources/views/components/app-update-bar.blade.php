{{-- "A new version of the app is available" (App\Support\AppUpdate, owner 20 Nov 2026) --}}
@if(\App\Support\AppUpdate::needed(request()))
    <div id="app-update-bar" class="bg-(--color-primary) text-white" role="status">
        <div class="max-w-6xl mx-auto px-4 py-2.5 flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-center">
            <span class="text-sm">A new version of the {{ config('app.name') }} app is available.</span>
            <a href="{{ \App\Support\AppUpdate::playUrl() }}" class="shrink-0 inline-flex items-center bg-white text-sm font-bold px-4 py-1.5 rounded-lg hover:bg-white/90 transition-colors" style="color: var(--color-primary);">Update</a>
            <button type="button" class="shrink-0 text-sm text-white/90 underline"
                onclick="try { document.cookie = '{{ \App\Support\AppUpdate::LATER_COOKIE }}=1; max-age=259200; path=/; secure; samesite=lax'; } catch (e) {} document.getElementById('app-update-bar').remove();">Later</button>
        </div>
        @if(\App\Support\AppUpdate::versionOf(request()->userAgent()) === '')
            {{-- apps from before 1.0.4 open Play links inside the app, so say how to do it by hand --}}
            <p class="text-xs text-white/90 text-center pb-2 px-4">If the button does not open the Play Store, open the Play Store app, search for "{{ config('app.name') }}" and tap Update.</p>
        @endif
    </div>
@endif
