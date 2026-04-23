<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\CallLog;
use App\Models\Interest;
use App\Models\Lead;
use App\Models\ProfilePhoto;
use App\Models\ProfileView;
use App\Models\StaffTarget;
use App\Models\Subscription;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Removes all demo data created by matrimony:demo-seed.
 *
 * IDENTIFICATION MARKERS used by the seeder and honored by this cleaner:
 *   - Users:        email LIKE '%@demo.local'
 *   - Branches:     code LIKE 'DEMO-%'
 *   - Leads:        notes LIKE 'Demo lead%'
 *   - Call logs:    notes = 'Demo call log'
 *   - Subs:         razorpay_order_id LIKE 'order_DEMO_%' (or null + linked to demo user)
 *   - Staff target: notes LIKE 'Demo target%'
 *   - Testimonials: couple_names LIKE '[Demo]%'
 *   - Avatars:      storage/app/public/demo-avatars/*
 *
 * Real production data (non-demo) is NEVER touched.
 */
class ClearDemoData extends Command
{
    protected $signature = 'matrimony:demo-clean
                            {--confirm : Skip the confirmation prompt}';

    protected $description = 'Remove all demo data created by matrimony:demo-seed';

    public function handle(): int
    {
        // Bypass prompt with --confirm flag (for CI / scripts)
        if (! $this->option('confirm')) {
            if (! $this->confirm('This will DELETE all demo data (users, profiles, leads, etc.). Real data is preserved. Continue?', false)) {
                $this->info('Aborted.');
                return self::SUCCESS;
            }
        }

        $this->info('Clearing demo data...');

        DB::transaction(function () {
            // 1. Delete demo users → cascades to profiles, photos, subscriptions, etc.
            $demoUsers = User::where('email', 'LIKE', '%@demo.local')->get();
            $userIds = $demoUsers->pluck('id')->toArray();
            $this->line("  Deleting " . count($userIds) . " demo users (cascades to profiles, photos, etc.)");

            // Explicit cleanup of non-cascading relations before user delete
            CallLog::whereIn('called_by_staff_id', $userIds)->delete();
            Lead::whereIn('assigned_to_staff_id', $userIds)
                ->orWhereIn('created_by_staff_id', $userIds)
                ->orWhereIn('converted_by_staff_id', $userIds)
                ->delete();
            StaffTarget::whereIn('staff_user_id', $userIds)->delete();
            Subscription::whereIn('user_id', $userIds)->delete();
            ProfilePhoto::whereIn('profile_id', function ($q) use ($userIds) {
                $q->select('id')->from('profiles')->whereIn('user_id', $userIds);
            })->delete();
            ProfileView::whereIn('viewer_profile_id', function ($q) use ($userIds) {
                $q->select('id')->from('profiles')->whereIn('user_id', $userIds);
            })->orWhereIn('viewed_profile_id', function ($q) use ($userIds) {
                $q->select('id')->from('profiles')->whereIn('user_id', $userIds);
            })->delete();
            Interest::whereIn('sender_profile_id', function ($q) use ($userIds) {
                $q->select('id')->from('profiles')->whereIn('user_id', $userIds);
            })->orWhereIn('receiver_profile_id', function ($q) use ($userIds) {
                $q->select('id')->from('profiles')->whereIn('user_id', $userIds);
            })->delete();

            // Delete profiles — force delete (Profile has SoftDeletes + cascades to detail tables)
            DB::table('profiles')->whereIn('user_id', $userIds)->delete();
            // Cascading child tables: religious_info, location_info, etc.
            // These have onDelete('cascade') via foreign key — Laravel migrations confirm this.

            // Unlink branch managers before deleting the branch (to avoid FK issues)
            Branch::withTrashed()->where('code', 'LIKE', 'DEMO-%')->update(['manager_user_id' => null]);

            // Delete the demo users (no SoftDeletes on User)
            User::whereIn('id', $userIds)->delete();

            // 2. Leftover demo records with clear markers (any orphans)
            // Use forceDelete() for models with SoftDeletes to fully remove
            $leadDeleted = Lead::withTrashed()
                ->where(function ($q) {
                    $q->where('notes', 'LIKE', 'Demo lead%')
                      ->orWhere('email', 'LIKE', '%@demo.local');
                })
                ->forceDelete();
            $callDeleted = CallLog::where('notes', 'Demo call log')->delete();
            $targetDeleted = StaffTarget::where('notes', 'LIKE', 'Demo target%')->delete();
            $subDeleted = Subscription::where('razorpay_order_id', 'LIKE', 'order_DEMO_%')->delete();
            $testDeleted = Testimonial::where('couple_names', 'LIKE', '[Demo]%')->delete();

            $this->line("    - {$leadDeleted} leftover demo leads");
            $this->line("    - {$callDeleted} leftover demo call logs");
            $this->line("    - {$targetDeleted} leftover demo staff targets");
            $this->line("    - {$subDeleted} leftover demo subscriptions");
            $this->line("    - {$testDeleted} demo testimonials");

            // 3. Demo branches — force-delete (SoftDeletes + unique code constraint)
            $branchDeleted = Branch::withTrashed()->where('code', 'LIKE', 'DEMO-%')->forceDelete();
            $this->line("    - {$branchDeleted} demo branches");
        });

        // 4. Clean up avatar files
        if (Storage::disk('public')->exists('demo-avatars')) {
            Storage::disk('public')->deleteDirectory('demo-avatars');
            $this->line("  Removed storage/app/public/demo-avatars/ directory");
        }

        $this->info('Demo data cleared.');
        return self::SUCCESS;
    }
}
