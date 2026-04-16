<?php

namespace App\Console\Commands;

use App\Models\UserMembership;
use App\Services\NotificationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('membership:expiry-reminders')]
#[Description('Send reminder notifications to users whose membership is expiring in 3 days or has expired today')]
class SendMembershipExpiryReminders extends Command
{
    public function handle(NotificationService $notificationService): int
    {
        $siteName = \App\Models\SiteSetting::getValue('site_name', 'Matrimony');

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

            // Email
            try {
                Mail::raw(
                    "Dear {$user->name},\n\n" .
                    "Your {$membership->plan->plan_name} plan on {$siteName} expires on {$membership->ends_at->format('d M Y')}.\n\n" .
                    "Renew your plan to continue enjoying premium features like viewing contacts, unlimited messaging, and highlighted profile.\n\n" .
                    "Visit: " . url('/membership-plans') . "\n\n" .
                    "Best regards,\n{$siteName} Team",
                    function ($message) use ($user, $siteName) {
                        $message->to($user->email)
                            ->subject("Your {$siteName} membership expires in 3 days");
                    }
                );
            } catch (\Throwable $e) {
                $this->warn("Failed to email {$user->email}: {$e->getMessage()}");
            }
        }

        $this->info("Sent {$expiringIn3Days->count()} expiring-in-3-days reminders.");

        // 2. Memberships that expired today — deactivate and notify
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

            try {
                Mail::raw(
                    "Dear {$user->name},\n\n" .
                    "Your {$membership->plan->plan_name} plan on {$siteName} has expired.\n\n" .
                    "You will no longer be able to view contact details, send messages, or see who viewed your profile.\n\n" .
                    "Renew now: " . url('/membership-plans') . "\n\n" .
                    "Best regards,\n{$siteName} Team",
                    function ($message) use ($user, $siteName) {
                        $message->to($user->email)
                            ->subject("Your {$siteName} membership has expired");
                    }
                );
            } catch (\Throwable $e) {
                $this->warn("Failed to email {$user->email}: {$e->getMessage()}");
            }
        }

        $this->info("Processed {$expiredToday->count()} expired memberships.");

        return self::SUCCESS;
    }
}
