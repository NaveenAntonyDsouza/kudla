<?php

use Illuminate\Support\Facades\Blade;

/*
| Weekly review, 4 Nov 2026: on Partner Preferences 15 members tapped
| something 59 times in 30 days with nothing happening. Every field there is
| this dropdown, and only its button opened the list: the label above it, the
| green "Any" pill and the chosen values did nothing. Now they open it too.
*/

it('opens the list from the label, the Any pill and the chosen values', function () {
    $html = Blade::render('<x-multi-select name="diet" label="Diet" :options="$o" />', ['o' => ['Vegetarian', 'Non-Vegetarian']]);

    expect($html)->toContain('cursor-pointer" @click="open = !open">Diet</label>')
        ->toContain('@click="open = true">Any</span>')
        ->toContain('mt-2 cursor-pointer" @click="open = true"')
        ->toContain('@click.stop="remove(val)"');   // the × on a chosen value still only removes it
});
