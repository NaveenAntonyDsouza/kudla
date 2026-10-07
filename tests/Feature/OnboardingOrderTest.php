<?php

/*
| Onboarding order (owner's choice, 7 Oct 2026): everything about YOU first,
| then the PARTNER, then photos —
|   1 About You → 2 Contact → 3 Lifestyle → 4 Partner → 5 Photos.
| Lifestyle used to come straight after Partner Preferences, so members
| answered eating / drinking / smoking for the partner they wanted.
*/

function onboardingView(string $name): string
{
    return file_get_contents(resource_path("views/onboarding/{$name}.blade.php"));
}

/** Route names a view links to, in page order. */
function onboardingLinks(string $view): array
{
    preg_match_all("/href=\"\\{\\{ route\\('(onboarding\\.[a-zA-Z0-9]+)'\\) \\}\\}\"/", onboardingView($view), $m);

    return $m[1];
}

/** The route a controller action redirects to (first redirect()->route in it). */
function onboardingRedirects(string $method): array
{
    $source = file_get_contents(app_path('Http/Controllers/OnboardingController.php'));
    preg_match('/function '.$method.'\(.*?(?=\n    (?:public|private|protected) function |\n}\s*$)/s', $source, $body);
    preg_match_all("/->route\\('([a-z.A-Z0-9]+)'\\)/", $body[0] ?? '', $m);

    return $m[1];
}

it('shows the steps in the new order on the progress bar', function () {
    $bar = file_get_contents(resource_path('views/components/onboarding-progress.blade.php'));

    preg_match_all("/(\\d) => '([^']+)'/", $bar, $m);
    expect(array_combine($m[1], $m[2]))->toBe(['1' => 'About You', '2' => 'Contact', '3' => 'Lifestyle', '4' => 'Partner', '5' => 'Photos'])
        // fits a phone: shorter connectors, and never cut off on the left
        ->and($bar)->toContain('.ob-progress { justify-content: safe center; }')
        ->toContain('.ob-progress .ob-line { width: 22px;')
        ->and(onboardingView('lifestyle'))->toStartWith('<x-layouts.onboarding title="Lifestyle & Social Media" :step="3"')
        ->and(onboardingView('partner-preferences'))->toStartWith('<x-layouts.onboarding title="Partner Preferences" :step="4"');
});

it('moves Save, Skip and Back through the new order', function () {
    // Save
    expect(onboardingRedirects('storeStep2'))->toBe(['onboarding.lifestyle'])
        ->and(onboardingRedirects('storeLifestyle'))->toBe(['onboarding.preferences'])
        ->and(onboardingRedirects('storePartnerPreferences'))->toBe(['matches.index', 'onboarding.photo']);

    // Skip (top + bottom) and Back on each page
    expect(onboardingLinks('step2'))->toBe(['onboarding.lifestyle', 'onboarding.step1', 'onboarding.lifestyle'])
        ->and(onboardingLinks('lifestyle'))->toBe(['onboarding.preferences', 'onboarding.step2', 'onboarding.preferences'])
        ->and(onboardingLinks('partner-preferences'))->toBe(['onboarding.photo', 'onboarding.lifestyle', 'onboarding.photo'])
        ->and(onboardingLinks('photo'))->toContain('onboarding.preferences')->not->toContain('onboarding.lifestyle');
});

it('says clearly which pages are about you and which is about the partner', function () {
    expect(onboardingView('lifestyle'))->toContain('<strong>About you</strong> — your own habits and interests')
        ->toContain('Your Lifestyle & Habits')
        ->and(onboardingView('partner-preferences'))->toContain("<strong>Now, the partner you're looking for.</strong>");
});

it('sends members who already finished setup back to their matches after editing preferences', function () {
    $source = file_get_contents(app_path('Http/Controllers/OnboardingController.php'));

    expect($source)->toContain("if (\$profile->onboarding_completed) {\n            return redirect()->route('matches.index')")
        ->and(file_get_contents(resource_path('views/matches/index.blade.php')))->toContain("@if(session('success'))");
});
