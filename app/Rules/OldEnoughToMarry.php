<?php

namespace App\Rules;

use App\Support\MinimumAge;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Date of birth must meet the site's minimum age for the chosen gender (App\Support\MinimumAge). */
class OldEnoughToMarry implements ValidationRule
{
    public function __construct(private ?string $gender) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $ok = MinimumAge::isOldEnough((string) $value, $this->gender);
        } catch (\Throwable) {
            return; // not a date — the 'date' rule reports that
        }

        if (! $ok) {
            $fail(MinimumAge::message($this->gender));
        }
    }
}
