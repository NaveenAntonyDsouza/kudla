{{-- Today's work queue (App\Support\WorkQueue). Inline styles only: custom admin
     views get no Tailwind build. Colours via opacity so dark mode stays legible. --}}
<x-filament-widgets::widget>
    <x-filament::section
        heading="Waiting for someone"
        description="Oldest first. Each line opens the page where the work is done.">
        @if (count($waiting) === 0)
            <p style="margin:0;font-size:.9rem;opacity:.8;">Nothing is waiting. All clear.</p>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
                    <thead>
                        <tr style="text-align:left;opacity:.7;font-size:.75rem;text-transform:uppercase;letter-spacing:.03em;">
                            <th style="padding:.4rem .5rem .4rem 0;font-weight:600;">Waiting</th>
                            <th style="padding:.4rem .5rem;font-weight:600;text-align:right;">How many</th>
                            <th style="padding:.4rem .5rem;font-weight:600;">Oldest has waited</th>
                            <th style="padding:.4rem .5rem;font-weight:600;">Target</th>
                            <th style="padding:.4rem 0 .4rem .5rem;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($waiting as $item)
                            <tr style="border-top:1px solid rgba(127,127,127,.2);">
                                <td style="padding:.6rem .5rem .6rem 0;font-weight:500;">{{ $item['label'] }}</td>
                                <td style="padding:.6rem .5rem;text-align:right;font-weight:700;font-size:1rem;">{{ number_format($item['count']) }}</td>
                                <td style="padding:.6rem .5rem;white-space:nowrap;">
                                    {{ $item['oldest'] ? $item['oldest']->diffForHumans(['parts' => 1, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) : '—' }}
                                    @if ($item['overdue'])
                                        <x-admin.pill color="danger">overdue</x-admin.pill>
                                    @endif
                                </td>
                                <td style="padding:.6rem .5rem;white-space:nowrap;opacity:.75;">{{ $item['target'] }}</td>
                                <td style="padding:.6rem 0 .6rem .5rem;text-align:right;white-space:nowrap;">
                                    @if ($item['url'])
                                        <x-filament::button tag="a" :href="$item['url']" size="sm" :color="$item['overdue'] ? 'danger' : 'primary'">Open</x-filament::button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if (count($clear) > 0)
            <p style="margin:1rem 0 0;font-size:.8rem;opacity:.7;">
                Nothing waiting: {{ collect($clear)->pluck('label')->map(fn ($l) => \Illuminate\Support\Str::before($l, ',') )->map(fn ($l) => \Illuminate\Support\Str::before($l, ' ('))->join(' · ') }}
            </p>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
