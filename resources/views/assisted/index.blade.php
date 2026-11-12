{{-- My Assisted Service (App\Http\Controllers\AssistedController, Nov 2026). The member-site CSS
     is prebuilt: only classes already used elsewhere on the site, plus inline styles. --}}
@php
    $card = 'background:#fff;border:1px solid #e5e7eb;border-radius:.75rem;padding:1.25rem;margin-bottom:1.25rem;';
    $h2 = 'font-size:1rem;font-weight:600;color:#111827;margin:0 0 .75rem;';
    $muted = 'font-size:.8rem;color:#6b7280;';
    $btn = 'display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1rem;border-radius:.5rem;font-size:.875rem;font-weight:600;border:0;cursor:pointer;';
    $stageStyle = [
        'waiting_member' => 'background:#fef3c7;color:#92400e;', 'member_no' => 'background:#f3f4f6;color:#4b5563;',
        'to_send' => 'background:#e0e7ff;color:#3730a3;', 'sent' => 'background:#dbeafe;color:#1e40af;',
        'accepted' => 'background:#dcfce7;color:#166534;', 'declined' => 'background:#f3f4f6;color:#4b5563;',
        'closed' => 'background:#f3f4f6;color:#4b5563;', 'meeting' => 'background:#fae8ff;color:#86198f;',
    ];
    $summary = fn ($p) => collect([$p->age ? $p->age.' Yrs' : null, $p->religiousInfo?->religion, $p->educationDetail?->occupation, $p->locationInfo?->native_state])->filter()->implode(', ');
@endphp
<x-layouts.app title="My Assisted Service">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <p class="text-sm text-gray-500 mb-6">
            <a href="{{ route('dashboard') }}" class="hover:text-(--color-primary)">My Home</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700 font-medium">My Assisted Service</span>
        </p>

        @if(session('success'))
            <div class="mb-6 p-3 bg-green-50 border border-green-200 rounded-lg"><p class="text-sm text-green-700 font-medium">{{ session('success') }}</p></div>
        @endif
        @if(session('error') || $errors->any())
            <div style="margin-bottom:1.5rem;padding:.75rem;background:#fef2f2;border:1px solid #fecaca;border-radius:.5rem;"><p style="font-size:.875rem;color:#b91c1c;font-weight:500;margin:0;">{{ session('error') ?? $errors->first() }}</p></div>
        @endif

        <h1 class="text-xl font-semibold text-gray-900 mb-6">My Assisted Service</h1>

        {{-- Manager --}}
        <div style="{{ $card }}display:flex;gap:1rem;align-items:center;flex-wrap:wrap;">
            <div style="flex:1 1 16rem;">
                <p style="{{ $muted }}margin:0;">Your relationship manager</p>
                <p style="font-size:1.1rem;font-weight:600;color:#111827;margin:.15rem 0 0;">{{ $manager?->name ?? 'Being chosen for you' }}</p>
                <p style="{{ $muted }}margin:.25rem 0 0;">
                    @if($plan){{ $plan->plan_name }}{{ $ends ? ' · until '.$ends->displayTz()->format('d M Y') : '' }}@else Your assisted plan has ended. Your history stays below. @endif
                </p>
            </div>
            @if($manager && $whatsapp)
                <a href="{{ $whatsapp }}" target="_blank" rel="noopener" style="{{ $btn }}background:#16a34a;color:#fff;text-decoration:none;">WhatsApp {{ \Illuminate\Support\Str::before($manager->name, ' ') }}</a>
            @endif
        </div>

        {{-- Consent --}}
        <div style="{{ $card }}">
            <h2 style="{{ $h2 }}">Your permission</h2>
            @if($profile->assisted_consent_at)
                <p style="font-size:.875rem;color:#374151;margin:0 0 .75rem;">You allowed your manager to work on your profile on <strong>{{ $profile->assisted_consent_at->displayTz()->format('d M Y') }}</strong>. They suggest matches for you, send the interests you agree to, and answer interests you receive only when you tell them to. Everything they do is listed below.</p>
                <form method="POST" action="{{ route('assisted.autoSend') }}" style="margin:0 0 .75rem;">
                    @csrf
                    <input type="hidden" name="auto_send" value="{{ $profile->assisted_auto_send ? 0 : 1 }}">
                    <p style="font-size:.875rem;color:#374151;margin:0 0 .4rem;">
                        {{ $profile->assisted_auto_send ? 'Your manager sends interests to the matches they choose without asking you first.' : 'Your manager asks you before sending each interest.' }}
                    </p>
                    <button type="submit" style="{{ $btn }}background:#f3f4f6;color:#374151;">{{ $profile->assisted_auto_send ? 'Ask me first instead' : 'Let my manager send without asking me' }}</button>
                </form>
                <form method="POST" action="{{ route('assisted.withdraw') }}" onsubmit="return confirm('Stop the assisted service? Your manager will no longer be able to act for you.');" style="margin:0;">
                    @csrf
                    <button type="submit" style="{{ $btn }}background:#fff;color:#b91c1c;border:1px solid #fecaca;">Stop: my manager may no longer act for me</button>
                </form>
            @elseif($plan)
                <p style="font-size:.875rem;color:#374151;margin:0 0 .75rem;">Your manager can start once you allow it. With your permission they will:</p>
                <ul style="font-size:.875rem;color:#374151;margin:0 0 .75rem 1.25rem;list-style:disc;">
                    <li>suggest matches for you here; you say yes or no to each</li>
                    <li>send an interest in your name to each match you say yes to</li>
                    <li>answer interests you receive, only when you tell them what to answer</li>
                    <li>arrange calls and meetings with families who accept</li>
                </ul>
                <form method="POST" action="{{ route('assisted.consent') }}" style="margin:0;">
                    @csrf
                    <label style="display:flex;gap:.5rem;align-items:flex-start;font-size:.875rem;color:#111827;margin-bottom:.5rem;">
                        <input type="checkbox" name="agree" value="1" required style="margin-top:.2rem;">
                        <span>I allow {{ \App\Models\SiteSetting::getValue('site_name', config('app.name')) }} staff{{ $manager ? ' ('.$manager->name.')' : '' }} to work on my profile as described above. I can stop this here at any time.</span>
                    </label>
                    <label style="display:flex;gap:.5rem;align-items:flex-start;font-size:.875rem;color:#374151;margin-bottom:.75rem;">
                        <input type="checkbox" name="auto_send" value="1" style="margin-top:.2rem;">
                        <span>My manager may also send interests to matches they choose without asking me first.</span>
                    </label>
                    <button type="submit" class="bg-(--color-primary) hover:bg-(--color-primary-hover)" style="{{ $btn }}color:#fff;">Allow my manager</button>
                </form>
            @else
                <p style="font-size:.875rem;color:#374151;margin:0;">Your manager can no longer act for you.</p>
            @endif
        </div>

        {{-- Waiting for your answer --}}
        @if($waiting->isNotEmpty())
            <div style="{{ $card }}">
                <h2 style="{{ $h2 }}">Matches waiting for your answer ({{ $waiting->count() }})</h2>
                <p style="{{ $muted }}margin:-.4rem 0 .75rem;">Yes sends an interest in your name. No: your manager will not contact them.</p>
                <div class="divide-y divide-gray-100">
                    @foreach($waiting as $rec)
                        @php $o = $rec->recommendedProfile; @endphp
                        <div style="display:flex;gap:1rem;align-items:center;padding:.75rem 0;flex-wrap:wrap;">
                            <a href="{{ route('profile.view', $o) }}" style="display:flex;gap:.75rem;align-items:center;flex:1 1 16rem;text-decoration:none;">
                                <div class="w-12 h-12 rounded-full bg-gray-100 overflow-hidden shrink-0">
                                    @if($__photoUrl = \App\Support\PhotoVisibility::urlForCurrentViewer($o))<img src="{{ $__photoUrl }}" class="w-full h-full object-cover">@endif
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-(--color-primary)">{{ $o->matri_id }}</p>
                                    <p class="text-xs text-gray-500">{{ $summary($o) }}</p>
                                    @if($rec->admin_note)<p style="font-size:.8rem;color:#374151;margin:.2rem 0 0;">“{{ $rec->admin_note }}” — {{ $rec->adminUser?->name ?? 'your manager' }}</p>@endif
                                </div>
                            </a>
                            <form method="POST" action="{{ route('assisted.answer', $rec) }}" style="display:flex;gap:.5rem;margin:0;">
                                @csrf
                                <button type="submit" name="answer" value="yes" class="bg-(--color-primary) hover:bg-(--color-primary-hover)" style="{{ $btn }}color:#fff;">Yes, contact</button>
                                <button type="submit" name="answer" value="no" style="{{ $btn }}background:#f3f4f6;color:#374151;">No</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Report --}}
        <div style="{{ $card }}">
            <h2 style="{{ $h2 }}">Matches your manager suggested ({{ $recs->count() }})</h2>
            @if($recs->isEmpty())
                <p style="font-size:.875rem;color:#6b7280;margin:0;">Nothing yet. Your manager's suggestions will appear here.</p>
            @else
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
                        <thead><tr style="text-align:left;color:#6b7280;font-size:.75rem;text-transform:uppercase;letter-spacing:.03em;">
                            <th style="padding:.4rem .5rem .4rem 0;">Suggested</th><th style="padding:.4rem .5rem;">Match</th><th style="padding:.4rem .5rem;">Where it stands</th>
                        </tr></thead>
                        <tbody>
                            @foreach($recs as $rec)
                                @php $stage = $rec->stage(); $o = $rec->recommendedProfile; $visible = $o && in_array($o->id, $visibleIds, true); @endphp
                                <tr style="border-top:1px solid #f3f4f6;">
                                    <td style="padding:.5rem .5rem .5rem 0;white-space:nowrap;color:#6b7280;">{{ $rec->created_at->displayTz()->format('d M Y') }}</td>
                                    <td style="padding:.5rem;">
                                        @if($visible)<a href="{{ route('profile.view', $o) }}" class="text-(--color-primary)" style="font-weight:600;">{{ $o->matri_id }}</a> <span style="color:#6b7280;">{{ $summary($o) }}</span>
                                        @else<span style="color:#9ca3af;">Profile no longer available</span>@endif
                                    </td>
                                    <td style="padding:.5rem;">
                                        <span style="display:inline-block;padding:.1rem .5rem;border-radius:.375rem;font-size:.75rem;font-weight:600;{{ $stageStyle[$stage] ?? '' }}">{{ $stages[$stage] ?? $stage }}</span>
                                        @if($rec->meeting_on)<span style="color:#6b7280;"> {{ $rec->meeting_on->format('d M Y') }}{{ $rec->meeting_note ? ' · '.$rec->meeting_note : '' }}</span>@endif
                                        @if($rec->member_decision === 'auto')<span style="color:#6b7280;font-size:.75rem;"> (sent without asking, as you allowed)</span>@endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if($replies->isNotEmpty())
            <div style="{{ $card }}">
                <h2 style="{{ $h2 }}">Interests your manager answered for you</h2>
                <div class="divide-y divide-gray-100">
                    @foreach($replies as $r)
                        <div style="padding:.6rem 0;font-size:.875rem;color:#374151;">
                            <strong>{{ $r->reply_type === 'accept' ? 'Accepted' : 'Declined' }}</strong> the interest from {{ in_array($r->interest?->sender_profile_id, $visibleIds, true) ? $r->interest->senderProfile?->matri_id : 'a member' }}
                            <span style="color:#6b7280;">· {{ $r->created_at->displayTz()->format('d M Y') }}</span>
                            <div style="{{ $muted }}">Your instruction: {{ $r->member_instruction }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div style="{{ $card }}">
            <h2 style="{{ $h2 }}">History</h2>
            @forelse($history as $e)
                <div style="display:flex;gap:.75rem;padding:.45rem 0;border-top:1px solid #f3f4f6;font-size:.875rem;flex-wrap:wrap;">
                    <span style="min-width:8.5rem;color:#6b7280;">{{ $e->created_at?->displayTz()->format('d M Y, h:i A') }}</span>
                    <span style="flex:1 1 16rem;color:#374151;">{{ $describe($e) }}</span>
                </div>
            @empty
                <p style="font-size:.875rem;color:#6b7280;margin:0;">Nothing yet.</p>
            @endforelse
        </div>
    </div>
</x-layouts.app>
