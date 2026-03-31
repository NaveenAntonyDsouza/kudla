<?php

namespace App\Services;

use App\Models\Profile;

class ProfileCompletionService
{
    /**
     * Calculate the profile completion percentage.
     *
     * @return int Percentage from 0 to 100
     */
    public function calculate(Profile $profile): int
    {
        // TODO: Implement in Phase 3
        throw new \RuntimeException('ProfileCompletionService::calculate() not yet implemented.');
    }
}
