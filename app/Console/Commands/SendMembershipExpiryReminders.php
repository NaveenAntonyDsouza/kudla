<?php

namespace App\Console\Commands;

use App\Models\UserMembership;
use App\Services\MemberEmailService;
use App\Services\NotificationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('membership:expiry-reminders')]
#[Description('Remind members whose membership ends in 3 days or tomorrow, and close and notify those that ended today')]
class SendMembershipExpiryReminders extends Command
{
    public function handle(NotificationService $notificationService, MemberEmailService $memberEmails): int
    {
        // 1. Memberships expiring in exactly 3 days
        $expiringIn3Days = UserMembership::where('is_active', true)
            ->whereDate('ends_at', now()->addDays(3)->toDateString())
            ->with(['user', 'plan'])
            ->get();

        foreach ($expiringIn3Days as $membership) {
            $user = $membership->user;
            if (!$user) continue;

            $notificationService->send(
                $user,
                'membership_expiring',
                'Membership Expiring Soon',
                "Your {$membership->plan->plan_name} plan expires in 3 days on {$membership->ends_at->format('d M Y')}. Renew now to keep your premium features.",
                null,
                ['membership_id' => $membership->id]
            );

            // Email — the admin-editable 'membership-expiring' template.
            $memberEmails->membershipExpiring($membership);
        }

        $this->info("Sent {$expiringIn3Days->count()} expiring-in-3-days reminders.");

        // 2. Memberships ending tomorrow — a last email before premium switches off
        $endingTomorrow = UserMembership::where('is_active', true)
            ->whereDate('ends_at', now()->addDay()->toDateString())
            ->with(['user', 'plan'])
            ->get();

        foreach ($endingTomorrow as $membership) {
            $memberEmails->membershipEndingTomorrow($membership);
        }

        $this->info("Sent {$endingTomorrow->count()} ending-tomorrow reminders.");

        // 3. Memberships that expired today — deactivate and notify
        $expiredToday = UserMembership::where('is_active', true)
            ->whereDate('ends_at', now()->subDay()->toDateString())
            ->with(['user', 'plan'])
            ->get();

        foreach ($expiredToday as $membership) {
            $user = $membership->user;
            if (!$user) continue;

            // Deactivate
            $membership->update(['is_active' => false]);

            $notificationService->send(
                $user,
                'membership_expired',
                'Membership Expired',
                "Your {$membership->plan->plan_name} plan has expired. Renew to continue using premium features.",
                null,
                ['membership_id' => $membership->id]
            );

            // Email — the admin-editable 'membership-expired' template (skips members without an email)
            $memberEmails->membershipExpired($membership);
        }

        $this->info("Processed {$expiredToday->count()} expired memberships.");

        return self::SUCCESS;
    }
}
