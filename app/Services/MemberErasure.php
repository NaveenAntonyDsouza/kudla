<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use App\Models\Profile;
use App\Models\ProfilePhoto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Keeps the privacy policy's promise (Oct 2026): 90 days after a member uses
 * "Delete Account", their personal data is deleted or anonymised. Until then
 * staff can still restore the account (reactivating clears the request).
 *
 * Deleted: photos (files too), ID proofs, horoscope file, contact / family /
 * education / location / lifestyle / religious / social details, partner
 * preferences, saved searches, notes, call logs, WhatsApp messages, devices,
 * sessions, login history, notifications, and their shortlists, blocks,
 * views, photo requests and match scores (both directions).
 * Anonymised: the user and profile rows (kept so Matri ID, payments and
 * reports stay consistent), their interest / chat messages, contact-form
 * messages, leads and email log addresses.
 * Kept as is: payments, memberships, subscriptions (accounting law) and
 * reports about the member (safety).
 */
class MemberErasure
{
    public const AFTER_DAYS = 90;

    /** Profiles whose deletion request is older than 90 days and still not erased. */
    public function due(): Builder
    {
        return Profile::withTrashed()
            ->whereNotNull('deletion_reason')
            ->whereNotNull('account_deleted_at')
            ->where('account_deleted_at', '<=', now()->subDays(self::AFTER_DAYS))
            ->where('is_active', false)
            ->whereNull('data_erased_at');
    }

    public function erase(Profile $profile): void
    {
        $pid = $profile->id;
        $uid = $profile->user_id;

        // files first (photos may live on different storage drivers)
        $images = app(ImageProcessingService::class);
        foreach (ProfilePhoto::where('profile_id', $pid)->get() as $photo) {
            try {
                $images->deleteVariants($photo->getAllStoragePaths(), $photo->storage_driver ?: 'public');
            } catch (\Throwable $e) {
                report($e);
            }
        }
        // ID proof scans: private disk (or public, if saved before Oct 2026)
        foreach (array_merge(
            $this->column('id_proofs', 'document_url', 'profile_id', $pid),
            $this->column('id_proofs', 'document_back_url', 'profile_id', $pid),
        ) as $path) {
            \App\Support\IdProofFiles::delete($path);
        }
        $files = $this->column('religious_info', 'jathakam_upload_url', 'profile_id', $pid);
        foreach (array_filter($files) as $path) {
            try {
                if (! str_starts_with((string) $path, 'http')) {
                    Storage::disk('public')->delete($path);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        DB::transaction(function () use ($profile, $pid, $uid) {
            foreach (['contact_info', 'differently_abled_info', 'education_details', 'family_details', 'lifestyle_info',
                'location_info', 'partner_preferences', 'religious_info', 'social_media_links', 'photo_privacy_settings',
                'saved_searches', 'daily_interest_usage', 'call_logs', 'profile_notes', 'whatsapp_messages', 'id_proofs',
                'profile_photos'] as $table) {
                $this->deleteWhere($table, ['profile_id' => $pid]);
            }
            foreach ([['match_scores', 'profile_id', 'matched_profile_id'], ['shortlists', 'profile_id', 'shortlisted_profile_id'],
                ['ignored_profiles', 'profile_id', 'ignored_profile_id'], ['blocked_profiles', 'profile_id', 'blocked_profile_id'],
                ['photo_access_grants', 'grantor_profile_id', 'grantee_profile_id'], ['photo_requests', 'requester_profile_id', 'target_profile_id'],
                ['profile_views', 'viewer_profile_id', 'viewed_profile_id'], ['contact_views', 'viewer_profile_id', 'target_profile_id'],
                ['admin_recommendations', 'for_profile_id', 'recommended_profile_id']] as [$table, $a, $b]) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->where($a, $pid)->orWhere($b, $pid)->delete();
                }
            }
            if ($uid) {
                foreach (['devices', 'sessions', 'login_history'] as $table) {
                    $this->deleteWhere($table, ['user_id' => $uid]);
                }
            }
            if (Schema::hasTable('notifications')) {
                DB::table('notifications')->where('profile_id', $pid)->when($uid, fn ($q) => $q->orWhere('user_id', $uid))->delete();
            }

            // what they wrote to other members
            $this->updateWhere('interests', ['sender_profile_id' => $pid], ['custom_message' => null]);
            $this->updateWhere('interest_replies', ['replier_profile_id' => $pid], ['custom_message' => null]);
            $this->updateWhere('leads', ['profile_id' => $pid], ['full_name' => 'Deleted member', 'phone' => null, 'email' => null, 'notes' => null]);
            if ($uid) {
                $this->updateWhere('contact_submissions', ['user_id' => $uid], ['name' => 'Deleted member', 'email' => null, 'phone' => null, 'message' => '[removed]', 'ip_address' => null]);
                $this->updateWhere('email_logs', ['user_id' => $uid], ['recipient' => null]);
                DB::table('users')->where('id', $uid)->update(array_filter([
                    'name' => 'Deleted member',
                    'email' => 'deleted-'.$uid.'@deleted.invalid',
                    'phone' => null,
                    'password' => Hash::make(Str::random(40)),
                    'remember_token' => null,
                    'notification_preferences' => null,
                    'email_undeliverable_address' => null,
                    'is_active' => false,
                    'updated_at' => now(),
                ], fn ($v, $k) => Schema::hasColumn('users', $k), ARRAY_FILTER_USE_BOTH));
            }

            DB::table('profiles')->where('id', $pid)->update([
                'full_name' => 'Deleted member',
                'date_of_birth' => '1900-01-01', // not nullable; the row is removed from the site anyway
                'creator_name' => null,
                'creator_contact_number' => null,
                'about_me' => null,
                'height' => null,
                'weight_kg' => null,
                'complexion' => null,
                'body_type' => null,
                'blood_group' => null,
                'mother_tongue' => null,
                'how_did_you_hear_about_us' => null,
                'is_active' => false,
                'is_hidden' => true,
                'data_erased_at' => now(),
                'deleted_at' => $profile->deleted_at ?? now(),
                'updated_at' => now(),
            ]);
        });

        try {
            AdminActivityLog::create([
                'admin_user_id' => AdminActivityLog::systemAdminId(),
                'action' => 'member_data_erased',
                'model_type' => Profile::class,
                'model_id' => $pid,
                'changes' => ['matri_id' => $profile->matri_id, 'deletion_requested' => (string) $profile->account_deleted_at, 'after_days' => self::AFTER_DAYS],
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function column(string $table, string $col, string $key, int $id): array
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $col)
            ? DB::table($table)->where($key, $id)->pluck($col)->all()
            : [];
    }

    private function deleteWhere(string $table, array $where): void
    {
        if (Schema::hasTable($table) && Schema::hasColumn($table, array_key_first($where))) {
            DB::table($table)->where($where)->delete();
        }
    }

    private function updateWhere(string $table, array $where, array $values): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, array_key_first($where))) {
            return;
        }
        $values = array_filter($values, fn ($v, $k) => Schema::hasColumn($table, $k), ARRAY_FILTER_USE_BOTH);
        if ($values) {
            DB::table($table)->where($where)->update($values);
        }
    }
}
