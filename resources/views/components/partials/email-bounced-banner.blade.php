{{-- "Your email is not reaching you" (Nov 2026): 46 members had a bouncing
     address and were waiting for a staff phone call, hearing nothing from the
     site meanwhile. They can now give a new address themselves. --}}
@auth
    @if (auth()->user()->staff_role_id === null
        && auth()->user()->email_undeliverable_at
        && ! request()->routeIs('email.fix'))
        <div class="px-4 py-3" style="background: #fdf0e3; border-bottom: 1px solid #f3d5b0;">
            <div class="max-w-5xl mx-auto flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm" style="color: #7a4a10;">
                    <span class="font-semibold">Your email is not reaching you.</span>
                    Matches and interest alerts are coming back to us, so you are not seeing them.
                </p>
                <a href="{{ route('email.fix') }}"
                   class="shrink-0 px-3 py-1.5 rounded-lg text-sm font-medium text-white"
                   style="background: var(--color-primary);">
                    Fix my email
                </a>
            </div>
        </div>
    @endif
@endauth
