<?php

namespace App\Support;

use App\Filament\Pages\DeleteRequests;
use App\Filament\Pages\FollowUpReport;
use App\Filament\Pages\UnlistedCommunities;
use App\Filament\Pages\WhatsAppInbox;
use App\Filament\Resources\ContactSubmissionResource;
use App\Filament\Resources\DocumentApprovalResource;
use App\Filament\Resources\IdProofResource;
use App\Filament\Resources\PaymentHistoryResource;
use App\Filament\Resources\PhotoApprovalResource;
use App\Filament\Resources\ProfileReportResource;
use App\Filament\Resources\UserResource;
use App\Models\ContactSubmission;
use App\Models\IdProof;
use App\Models\Profile;
use App\Models\ProfileNote;
use App\Models\ProfilePhoto;
use App\Models\ProfileReport;
use App\Models\ReligiousInfo;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Carbon;

/**
 * Today's work queue (admin audit, Oct 2026): everything waiting for a person,
 * from the eight menu pages staff used to check one by one, with how long the
 * oldest item has waited against a target time. Each line opens the page
 * where the work is done, filtered to what's waiting; lines for pages the
 * signed-in person can't open are left out.
 */
class WorkQueue
{
    /**
     * @return list<array{key: string, label: string, count: int, oldest: ?Carbon, target_hours: ?int, target: string, url: ?string, overdue: bool}>
     */
    public static function items(): array
    {
        $items = [];
        foreach (self::definitions() as $key => $d) {
            try {
                if (! ($d['can'])()) {
                    continue;
                }
                [$count, $oldest] = ($d['measure'])();
            } catch (\Throwable) {
                continue; // a table not on this site yet: leave the line out
            }
            $oldest = $oldest ? Carbon::parse($oldest) : null;
            $items[] = [
                'key' => $key,
                'label' => $d['label'],
                'count' => (int) $count,
                'oldest' => $oldest,
                'target_hours' => $d['target_hours'],
                'target' => $d['target'],
                'url' => $d['url'],
                'overdue' => $count > 0 && $oldest && $d['target_hours'] !== null
                    && $oldest->lt(now()->subHours($d['target_hours'])),
            ];
        }

        // overdue first, then the longest waiting; nothing-waiting lines last
        usort($items, fn ($a, $b) => [$b['count'] > 0, $b['overdue'], $a['oldest']?->timestamp ?? PHP_INT_MAX]
            <=> [$a['count'] > 0, $a['overdue'], $b['oldest']?->timestamp ?? PHP_INT_MAX]);

        return $items;
    }

    private static function definitions(): array
    {
        $pending = fn ($query, string $column = 'created_at') => [(clone $query)->count(), (clone $query)->min($column)];

        return [
            'profiles' => [
                'label' => 'Profiles waiting for approval',
                'target_hours' => 24, 'target' => '1 day',
                'can' => fn () => UserResource::canAccess(),
                'url' => self::url(fn () => UserResource::getUrl('index', ['activeTab' => 'pending'])),
                'measure' => fn () => $pending(Profile::whereNotNull('full_name')->where('is_approved', false)),
            ],
            'photos' => [
                'label' => 'Photos waiting for approval',
                'target_hours' => 24, 'target' => '1 day',
                'can' => fn () => PhotoApprovalResource::canAccess(),
                'url' => self::url(fn () => PhotoApprovalResource::getUrl('index', ['activeTab' => 'pending'])),
                'measure' => fn () => $pending(ProfilePhoto::where('approval_status', 'pending')),
            ],
            'id_proofs' => [
                'label' => 'ID proofs to check',
                'target_hours' => 48, 'target' => '2 days',
                'can' => fn () => IdProofResource::canAccess(),
                'url' => self::url(fn () => IdProofResource::getUrl('index', ['activeTab' => 'pending'])),
                'measure' => fn () => $pending(IdProof::where('verification_status', 'pending')),
            ],
            'documents' => [
                'label' => DocumentApprovalResource::getNavigationLabel() . ' to check',
                'target_hours' => 48, 'target' => '2 days',
                'can' => fn () => DocumentApprovalResource::canAccess(),
                'url' => self::url(fn () => DocumentApprovalResource::getUrl('index')),
                'measure' => fn () => $pending(ReligiousInfo::whereNotNull('jathakam_upload_url')->where('jathakam_approval_status', 'pending'), 'updated_at'),
            ],
            'contact' => [
                'label' => 'Contact messages to answer',
                'target_hours' => 24, 'target' => '1 day',
                'can' => fn () => ContactSubmissionResource::canAccess(),
                'url' => self::url(fn () => ContactSubmissionResource::getUrl('index', ['activeTab' => 'new'])),
                'measure' => fn () => $pending(ContactSubmission::where('status', 'new')),
            ],
            'reports' => [
                'label' => 'Profiles reported by members',
                'target_hours' => 12, 'target' => 'same day',
                'can' => fn () => ProfileReportResource::canAccess(),
                'url' => self::url(fn () => ProfileReportResource::getUrl('index', ['activeTab' => 'pending'])),
                'measure' => fn () => $pending(ProfileReport::where('status', 'pending')),
            ],
            'followups' => [
                'label' => 'Follow-ups due',
                'target_hours' => 24, 'target' => 'on the day',
                'can' => fn () => FollowUpReport::canAccess(),
                'url' => self::url(fn () => FollowUpReport::getUrl()),
                'measure' => fn () => $pending(ProfileNote::pendingFollowUp()->whereHas('profile')->where('follow_up_date', '<=', today()), 'follow_up_date'),
            ],
            'whatsapp' => [
                'label' => 'WhatsApp chats not read',
                'target_hours' => 12, 'target' => 'same day',
                'can' => fn () => WhatsAppInbox::shouldRegisterNavigation(),
                'url' => self::url(fn () => WhatsAppInbox::getUrl()),
                'measure' => function () {
                    $unread = WhatsAppMessage::where('direction', WhatsAppMessage::IN)->whereNull('read_at');

                    return [(clone $unread)->distinct()->count('wa_id'), (clone $unread)->min('created_at')];
                },
            ],
            'failed_payments' => [
                'label' => 'Failed payments, last 7 days (call the member)',
                'target_hours' => 24, 'target' => '1 day',
                'can' => fn () => PaymentHistoryResource::canAccess(),
                'url' => self::url(fn () => PaymentHistoryResource::getUrl('index', ['activeTab' => 'failed'])),
                'measure' => fn () => $pending(Subscription::where('payment_status', 'failed')->where('created_at', '>=', now()->subDays(7))),
            ],
            'bounced' => [
                'label' => 'Members whose email bounced (phone them for the right address)',
                'target_hours' => 24 * 7, 'target' => '1 week',
                'can' => fn () => UserResource::canAccess(),
                'url' => self::url(fn () => UserResource::getUrl('index', ['tableFilters' => ['email_bounced' => ['isActive' => true]]])),
                // live members only; the line clears when their address is corrected
                'measure' => fn () => $pending(User::whereNull('staff_role_id')->whereNotNull('email_undeliverable_at')->whereHas('profile'), 'email_undeliverable_at'),
            ],
            'unlisted' => [
                'label' => 'Castes and religions typed in by members (add to the lists)',
                'target_hours' => 24 * 7, 'target' => '1 week',
                'can' => fn () => UnlistedCommunities::canAccess(),
                'url' => self::url(fn () => UnlistedCommunities::getUrl()),
                'measure' => fn () => $pending(ReligiousInfo::query()->whereHas('profile')->where(function ($w) {
                    foreach (['other_caste_name', 'other_religion_name', 'other_denomination_name'] as $col) {
                        $w->orWhere(fn ($o) => $o->whereNotNull($col)->where($col, '!=', ''));
                    }
                }), 'updated_at'),
            ],
            'deleted' => [
                'label' => 'Accounts deleted, last 7 days (for your information)',
                'target_hours' => null, 'target' => '—',
                'can' => fn () => DeleteRequests::canAccess(),
                'url' => self::url(fn () => DeleteRequests::getUrl()),
                'measure' => fn () => $pending(Profile::onlyTrashed()->whereNotNull('full_name')->where('deleted_at', '>=', now()->subDays(7)), 'deleted_at'),
            ],
        ];
    }

    private static function url(\Closure $make): ?string
    {
        try {
            return $make();
        } catch (\Throwable) {
            return null;
        }
    }
}
