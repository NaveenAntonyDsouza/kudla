{{-- Last 30 days / 90 days / 12 months, for Insights pages with a public $days. --}}
<div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:1rem;">
    @foreach ([30 => 'Last 30 days', 90 => 'Last 90 days', 365 => 'Last 12 months'] as $value => $label)
        <x-filament::button size="sm" :color="$days === $value ? 'primary' : 'gray'" :outlined="$days !== $value" wire:click="$set('days', {{ $value }})">{{ $label }}</x-filament::button>
    @endforeach
</div>
