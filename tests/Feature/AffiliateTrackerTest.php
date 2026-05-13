<?php

use App\Models\AffiliateClick;
use App\Models\Branch;
use App\Models\User;
use App\Services\AffiliateTracker;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| AffiliateTracker — registration attribution
|--------------------------------------------------------------------------
| The bug we're fixing here (May 2026): direct signups (no /r/CODE
| affiliate cookie) were leaving user.branch_id = NULL, which then
| cascaded into Profile.branch_id = NULL via RegisterController. Once
| BranchScopable went live in Stage 2, that meant branch-bound staff
| literally couldn't see those signups in the admin panel — a single
| Kudla admin reported they could see only 50 of 134 profiles
| (only the affiliate-attributed half).
|
| The fix: when no affiliate cookie is present, attributeRegistration
| falls back to the first active branch (conventionally "Head Office"
| at id=1). On a single-branch deployment that's unambiguous; on a
| multi-branch deployment it routes default web traffic to HQ for
| triage. Either way no signup ever goes orphan again.
|
| These three tests pin that behaviour: the fallback path, the
| original cookie path (regression-proof), and a no-branches-at-all
| edge case where the service should bail rather than crash.
*/

function createAffiliateTrackerSchema(): void
{
    if (! Schema::hasTable('branches')) {
        Schema::create('branches', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('code')->unique();
            $t->string('location')->nullable();
            $t->boolean('is_active')->default(true);
            $t->boolean('is_head_office')->default(false);
            $t->timestamps();
            // Branch uses the SoftDeletes trait — scopeActive() chains a
            // whereNull('deleted_at'), so the column must exist or every
            // Branch::active()->… query 500s.
            $t->softDeletes();
        });
    }

    if (! Schema::hasTable('users')) {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('phone')->nullable();
            $t->string('password')->nullable();
            $t->string('role')->default('user');
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->unsignedBigInteger('staff_role_id')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
    }

    if (! Schema::hasTable('affiliate_clicks')) {
        Schema::create('affiliate_clicks', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('branch_id');
            $t->string('ip_hash')->nullable();
            $t->string('user_agent_hash')->nullable();
            $t->string('referrer_url', 500)->nullable();
            $t->string('landing_page', 500)->nullable();
            $t->string('utm_source', 100)->nullable();
            $t->string('utm_medium', 100)->nullable();
            $t->string('utm_campaign', 100)->nullable();
            $t->timestamp('visited_at')->nullable();
            $t->unsignedBigInteger('registered_user_id')->nullable();
            $t->timestamp('registered_at')->nullable();
            $t->timestamp('converted_at')->nullable();
            $t->timestamps();
        });
    }
}

beforeEach(function () {
    createAffiliateTrackerSchema();
});

afterEach(function () {
    \DB::table('affiliate_clicks')->delete();
    \DB::table('users')->delete();
    \DB::table('branches')->delete();
});

it('falls back to the first active branch when no affiliate cookie is set', function () {
    // Two active branches; the lowest-id one is Head Office.
    $ho = Branch::create(['name' => 'Head Office', 'code' => 'HO', 'is_active' => true, 'is_head_office' => true]);
    Branch::create(['name' => 'Mangalore', 'code' => 'MNG', 'is_active' => true]);

    $user = User::create([
        'name' => 'Direct Signup',
        'email' => 'direct@example.com',
        'phone' => '9700000001',
    ]);

    // Request with NO affiliate cookie
    $request = Request::create('/register', 'POST');

    app(AffiliateTracker::class)->attributeRegistration($request, $user);

    expect($user->fresh()->branch_id)->toBe($ho->id);

    // No AffiliateClick row should be created — there was no click to begin with.
    expect(AffiliateClick::count())->toBe(0);
});

it('uses the cookie-attributed branch when one is set (regression test)', function () {
    $ho = Branch::create(['name' => 'Head Office', 'code' => 'HO', 'is_active' => true, 'is_head_office' => true]);
    $mng = Branch::create(['name' => 'Mangalore', 'code' => 'MNG', 'is_active' => true]);

    $user = User::create([
        'name' => 'Affiliate Signup',
        'email' => 'mng@example.com',
        'phone' => '9700000002',
    ]);

    // Simulate the affiliate cookie that /r/MNG would have set
    $request = Request::create('/register', 'POST', [], [
        AffiliateTracker::COOKIE_NAME => 'MNG',
    ]);

    app(AffiliateTracker::class)->attributeRegistration($request, $user);

    // User is attributed to MNG, not the fallback HO
    expect($user->fresh()->branch_id)->toBe($mng->id);

    // A click row should now exist linking the user to MNG (synthetic, since
    // we didn't pre-create a real captureClick row).
    $click = AffiliateClick::where('registered_user_id', $user->id)->first();
    expect($click)->not->toBeNull();
    expect($click->branch_id)->toBe($mng->id);
});

it('bails gracefully when no branches are configured at all', function () {
    // No branches in the DB.
    $user = User::create([
        'name' => 'Orphan Signup',
        'email' => 'orphan@example.com',
        'phone' => '9700000003',
    ]);

    $request = Request::create('/register', 'POST');

    // Must not throw — registration should always complete even on a fresh
    // install where the BranchesSeeder hasn't run yet.
    app(AffiliateTracker::class)->attributeRegistration($request, $user);

    expect($user->fresh()->branch_id)->toBeNull();
    expect(AffiliateClick::count())->toBe(0);
});
