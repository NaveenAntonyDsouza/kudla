<?php

namespace App\Support;

use App\Models\AdminActivityLog;
use App\Models\IdProof;
use App\Models\ProfileNote;
use App\Models\ProfilePhoto;
use App\Models\ReligiousInfo;
use App\Models\User;
use Illuminate\Support\Facades\Event;

/**
 * Staff actions the Activity Log missed (12 Nov 2026, admin audit point 3):
 * photo, ID proof and horoscope/document approvals and rejections (single,
 * bulk, or from a member's page), photos and ID proofs deleted, and notes
 * added. Recorded from the records themselves, so every button that changes
 * them is covered, and only when a staff account is the one signed in — a
 * member uploading or deleting their own photo, and scheduled jobs, are not
 * staff actions. Each entry points at the member (Profile) so the Activity
 * Log shows who it was about. Never stops the action itself.
 */
final class StaffActionLog
{
    public static function register(): void
    {
        Event::listen('eloquent.updated: '.ProfilePhoto::class, fn (ProfilePhoto $p) => self::statusChanged($p, 'approval_status', 'photo', ['photo_id' => $p->id, 'reason' => $p->rejection_reason ?? null]));
        Event::listen('eloquent.deleted: '.ProfilePhoto::class, fn (ProfilePhoto $p) => self::record('photo_deleted', $p->profile_id, ['photo_id' => $p->id]));

        Event::listen('eloquent.updated: '.IdProof::class, fn (IdProof $d) => self::statusChanged($d, 'verification_status', 'id_proof', ['document_type' => $d->document_type]));
        Event::listen('eloquent.deleted: '.IdProof::class, fn (IdProof $d) => self::record('id_proof_deleted', $d->profile_id, ['document_type' => $d->document_type]));

        Event::listen('eloquent.updated: '.ReligiousInfo::class, fn (ReligiousInfo $r) => self::statusChanged($r, 'jathakam_approval_status', 'document', []));

        Event::listen('eloquent.created: '.ProfileNote::class, function (ProfileNote $n) {
            if (str_starts_with((string) $n->note, StaffActivity::PLAN_NOTE_PREFIX)) {
                return; // the Change Plan page's own note: that page logs plan_assigned
            }
            self::record('note_added', $n->profile_id, array_filter([
                'type' => $n->note_type ?: 'general',
                'follow_up' => $n->follow_up_date?->toDateString(),
            ]));
        });
    }

    /** approved / rejected (other values, e.g. back to pending, are not logged). */
    private static function statusChanged($model, string $column, string $what, array $details): void
    {
        if (! $model->wasChanged($column)) {
            return;
        }
        $status = (string) $model->{$column};
        if (! in_array($status, ['approved', 'rejected'], true)) {
            return;
        }
        if ($status === 'approved') {
            unset($details['reason']);
        }
        self::record("{$what}_{$status}", $model->profile_id, array_filter($details, fn ($v) => $v !== null && $v !== ''));
    }

    /** Write one Activity Log line for the signed-in staff account, if there is one. */
    public static function record(string $action, ?int $profileId, array $details = []): void
    {
        try {
            $staff = self::staff();
            if (! $staff) {
                return;
            }
            AdminActivityLog::create([
                'admin_user_id' => $staff->id,
                'action' => $action,
                'model_type' => $profileId ? 'Profile' : null,
                'model_id' => $profileId,
                'changes' => $details ?: null,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e); // logging must never stop the action
        }
    }

    private static function staff(): ?User
    {
        // the request's own sign-in: the admin panel's inside the panel. On the member
        // site that is a member (staff are signed out there), so nothing is logged.
        $user = auth()->user();

        return $user instanceof User && ($user->staff_role_id !== null || $user->role === 'admin') ? $user : null;
    }
}
