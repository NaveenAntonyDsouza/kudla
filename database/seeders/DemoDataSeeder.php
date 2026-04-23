<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\CallLog;
use App\Models\ContactInfo;
use App\Models\EducationDetail;
use App\Models\FamilyDetail;
use App\Models\Interest;
use App\Models\Lead;
use App\Models\LifestyleInfo;
use App\Models\LocationInfo;
use App\Models\MembershipPlan;
use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\ProfileView;
use App\Models\ReligiousInfo;
use App\Models\StaffRole;
use App\Models\StaffTarget;
use App\Models\Subscription;
use App\Models\Testimonial;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Seeds realistic demo data for the admin panel so dashboards, widgets, and
 * charts render populated state — ideal for CodeCanyon screenshots and for
 * buyers to explore the platform on fresh install.
 *
 * IDENTIFICATION CONVENTION
 * - All demo users have email ending in @demo.local
 * - Demo branch has code starting with DEMO-
 * - Demo testimonials have couple_names starting with "[Demo]"
 * Running ClearDemoData cleanly removes everything via these markers.
 *
 * Invoke via: php artisan matrimony:demo-seed
 */
class DemoDataSeeder extends Seeder
{
    // ----------------------------------------------------------------
    // DATA POOLS — realistic Indian matrimony context without copying
    // any real person. Names are common generic combinations.
    // ----------------------------------------------------------------

    private array $maleFirstNames = [
        'Arjun', 'Rahul', 'Amit', 'Vikram', 'Rohit', 'Karthik', 'Suresh',
        'Ravi', 'Pradeep', 'Nikhil', 'Akash', 'Rajesh', 'Deepak', 'Manoj',
        'Sanjay', 'Ajay', 'Vijay', 'Anil', 'Prashant', 'Harish',
    ];

    private array $femaleFirstNames = [
        'Priya', 'Anjali', 'Neha', 'Pooja', 'Kavita', 'Shalini', 'Divya',
        'Meera', 'Sneha', 'Ritu', 'Swati', 'Reena', 'Anita', 'Sunita',
        'Deepa', 'Asha', 'Geeta', 'Lakshmi', 'Radha', 'Sita',
    ];

    private array $surnames = [
        'Sharma', 'Verma', 'Kumar', 'Singh', 'Patel', 'Shah', 'Reddy',
        'Rao', 'Iyer', 'Nair', 'Menon', 'Pillai', 'Gupta', 'Mehta',
        'Agarwal', 'Mishra', 'Tiwari', 'Joshi', 'Bhat', 'Desai',
    ];

    private array $cities = [
        ['city' => 'Mumbai', 'state' => 'Maharashtra'],
        ['city' => 'Delhi', 'state' => 'Delhi'],
        ['city' => 'Bengaluru', 'state' => 'Karnataka'],
        ['city' => 'Chennai', 'state' => 'Tamil Nadu'],
        ['city' => 'Pune', 'state' => 'Maharashtra'],
        ['city' => 'Kolkata', 'state' => 'West Bengal'],
        ['city' => 'Hyderabad', 'state' => 'Telangana'],
        ['city' => 'Ahmedabad', 'state' => 'Gujarat'],
        ['city' => 'Kochi', 'state' => 'Kerala'],
        ['city' => 'Jaipur', 'state' => 'Rajasthan'],
    ];

    private array $professions = [
        'Software Engineer', 'Doctor', 'Teacher', 'Business Owner',
        'Chartered Accountant', 'Marketing Manager', 'Civil Engineer',
        'Bank Officer', 'Architect', 'Designer', 'Lawyer', 'Professor',
        'Nurse', 'Consultant', 'Entrepreneur',
    ];

    private array $educations = [
        'B.Tech/B.E.', 'M.Tech/M.E.', 'MBA', 'B.Com', 'M.Com',
        'MBBS', 'MD', 'B.A.', 'M.A.', 'CA', 'B.Sc', 'M.Sc', 'Ph.D',
    ];

    private array $religionsDist = [
        ['religion' => 'Hindu', 'weight' => 50],
        ['religion' => 'Christian', 'weight' => 30],
        ['religion' => 'Muslim', 'weight' => 15],
        ['religion' => 'Jain', 'weight' => 3],
        ['religion' => 'Sikh', 'weight' => 2],
    ];

    private array $avatarColors = [
        '#8B1D91', '#00BCD4', '#E53935', '#43A047', '#FB8C00',
        '#3949AB', '#00897B', '#D81B60', '#5E35B1', '#7CB342',
    ];

    // ----------------------------------------------------------------
    // CONFIGURATION
    // ----------------------------------------------------------------

    private int $memberCount = 50;
    private int $leadCount = 30;
    private int $callLogCount = 100;
    private int $subscriptionCount = 15;
    private int $interestCount = 20;
    private int $profileViewCount = 30;

    // ----------------------------------------------------------------
    // STATE (populated during run)
    // ----------------------------------------------------------------

    private ?Branch $headOffice = null;
    private ?Branch $demoBranch = null;
    private array $staffUsers = []; // role_slug => [User]
    private array $memberUsers = []; // [User]
    private array $memberProfiles = []; // [Profile]
    private array $plans = []; // slug => MembershipPlan

    public function run(): void
    {
        DB::transaction(function () {
            $this->loadBaselines();
            $this->seedDemoBranch();
            $this->seedStaff();
            $this->seedMembers();
            $this->seedLeadsAndCalls();
            $this->seedSubscriptions();
            $this->seedStaffTargets();
            $this->seedInterests();
            $this->seedProfileViews();
            $this->seedTestimonials();
        });

        $this->command?->info('Demo data seeded successfully.');
        $this->printSummary();
    }

    // ----------------------------------------------------------------
    // BASELINES — existing seed data required by the demo
    // ----------------------------------------------------------------

    private function loadBaselines(): void
    {
        $this->headOffice = Branch::where('is_head_office', true)->first();
        if (! $this->headOffice) {
            throw new \RuntimeException('Head office branch not found. Run BranchesSeeder first.');
        }

        $plans = MembershipPlan::all()->keyBy('slug');
        if ($plans->isEmpty()) {
            throw new \RuntimeException('No membership plans found. Run MembershipPlanSeeder first.');
        }
        $this->plans = $plans->toArray();
    }

    // ----------------------------------------------------------------
    // BRANCH
    // ----------------------------------------------------------------

    private function seedDemoBranch(): void
    {
        $this->demoBranch = Branch::firstOrCreate(
            ['code' => 'DEMO-MUM'],
            [
                'name' => 'Mumbai Branch (Demo)',
                'location' => 'Mumbai',
                'state' => 'Maharashtra',
                'address' => 'Andheri West, Mumbai',
                'phone' => '+91 22 0000 0000',
                'email' => 'mumbai@demo.local',
                'is_active' => true,
                'is_head_office' => false,
                'commission_pct' => 15.00,
                'notes' => 'Demo branch — safe to delete via matrimony:demo-clean',
            ]
        );
    }

    // ----------------------------------------------------------------
    // STAFF
    // ----------------------------------------------------------------

    private function seedStaff(): void
    {
        $spec = [
            ['role' => 'telecaller', 'first' => 'Rahul', 'last' => 'Sharma', 'branch' => 'head'],
            ['role' => 'telecaller', 'first' => 'Priya', 'last' => 'Patel', 'branch' => 'demo'],
            ['role' => 'branch_manager', 'first' => 'Amit', 'last' => 'Kumar', 'branch' => 'demo'],
            ['role' => 'support_agent', 'first' => 'Neha', 'last' => 'Singh', 'branch' => 'head'],
            ['role' => 'moderator', 'first' => 'Vikram', 'last' => 'Reddy', 'branch' => 'head'],
        ];

        foreach ($spec as $i => $s) {
            $role = StaffRole::where('slug', $s['role'])->first();
            if (! $role) {
                continue;
            }
            $branch = $s['branch'] === 'demo' ? $this->demoBranch : $this->headOffice;
            $email = strtolower("demo-{$s['first']}.{$s['last']}@demo.local");

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $s['first'] . ' ' . $s['last'],
                    'password' => Hash::make('password'),
                    'phone' => '+91 9' . str_pad((string) (800000000 + $i + 1), 9, '0', STR_PAD_LEFT),
                    'staff_role_id' => $role->id,
                    'branch_id' => $branch->id,
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'last_login_at' => now()->subHours(rand(1, 48)),
                ]
            );
            $this->staffUsers[$s['role']][] = $user;
        }

        // Set Mumbai branch manager
        if (! empty($this->staffUsers['branch_manager'])) {
            $this->demoBranch->update(['manager_user_id' => $this->staffUsers['branch_manager'][0]->id]);
        }
    }

    // ----------------------------------------------------------------
    // MEMBERS + PROFILES (the big one)
    // ----------------------------------------------------------------

    private function seedMembers(): void
    {
        Storage::disk('public')->makeDirectory('demo-avatars');

        for ($i = 0; $i < $this->memberCount; $i++) {
            $gender = rand(1, 100) <= 60 ? 'female' : 'male';
            $first = $gender === 'male'
                ? $this->maleFirstNames[array_rand($this->maleFirstNames)]
                : $this->femaleFirstNames[array_rand($this->femaleFirstNames)];
            $last = $this->surnames[array_rand($this->surnames)];
            $age = rand(22, 40);
            $religion = $this->pickReligion();
            $cityData = $this->cities[array_rand($this->cities)];
            $profession = $this->professions[array_rand($this->professions)];
            $education = $this->educations[array_rand($this->educations)];

            // Unique email + phone
            $emailSlug = strtolower("{$first}.{$last}") . ($i + 1);
            $email = "demo-{$emailSlug}@demo.local";
            $phone = '+91 9' . str_pad((string) (700000001 + $i), 9, '0', STR_PAD_LEFT);

            // About 90% are approved, 10% pending for admin moderation dashboard
            $isApproved = rand(1, 100) <= 90;

            // Some members were registered by a telecaller — for branch revenue + staff stats
            $createdByStaffId = null;
            $branchId = $this->headOffice->id;
            if (rand(1, 100) <= 40 && ! empty($this->staffUsers['telecaller'])) {
                $staff = $this->staffUsers['telecaller'][array_rand($this->staffUsers['telecaller'])];
                $createdByStaffId = $staff->id;
                $branchId = $staff->branch_id ?? $this->headOffice->id;
            }

            $user = User::create([
                'name' => "{$first} {$last}",
                'email' => $email,
                'password' => Hash::make('password'),
                'phone' => $phone,
                'branch_id' => $branchId,
                'is_active' => true,
                'email_verified_at' => now()->subDays(rand(1, 180)),
                'last_login_at' => rand(1, 100) <= 70 ? now()->subDays(rand(0, 14)) : now()->subDays(rand(30, 120)),
            ]);

            $dob = now()->subYears($age)->subDays(rand(0, 365));

            $profile = Profile::create([
                'user_id' => $user->id,
                'branch_id' => $branchId,
                'created_by_staff_id' => $createdByStaffId,
                'full_name' => "{$first} {$last}",
                'gender' => $gender,
                'date_of_birth' => $dob->toDateString(),
                'created_by' => $createdByStaffId ? 'parent' : 'self',
                'creator_name' => $createdByStaffId ? 'Admin (demo)' : null,
                'marital_status' => 'never_married',
                'height' => rand(150, 185),
                'weight_kg' => rand(45, 85),
                'complexion' => ['fair', 'wheatish', 'medium', 'dark'][rand(0, 3)],
                'body_type' => ['slim', 'average', 'athletic', 'heavy'][rand(0, 3)],
                'mother_tongue' => ['Hindi', 'English', 'Tamil', 'Telugu', 'Marathi', 'Malayalam', 'Kannada'][rand(0, 6)],
                'about_me' => "{$first} is a {$profession} based in {$cityData['city']}. Looking for a compatible life partner who shares similar values and interests.",
                'profile_completion_pct' => rand(40, 100),
                'onboarding_completed' => true,
                'is_active' => true,
                'is_approved' => $isApproved,
                'is_verified' => $isApproved && rand(1, 100) <= 60,
                'is_vip' => rand(1, 100) <= 5,
                'is_featured' => rand(1, 100) <= 10,
            ]);

            // Detail tables (one-to-one)
            ReligiousInfo::create([
                'profile_id' => $profile->id,
                'religion' => $religion,
                'caste' => $this->casteForReligion($religion),
            ]);

            LocationInfo::create([
                'profile_id' => $profile->id,
                'residing_country' => 'India',
                'native_country' => 'India',
                'native_state' => $cityData['state'],
                'native_district' => $cityData['city'],
            ]);

            EducationDetail::create([
                'profile_id' => $profile->id,
                'highest_education' => $education,
                'occupation' => $profession,
                'employer_name' => $this->pickEmployer(),
                'annual_income' => rand(3, 25) * 100000,
                'working_city' => $cityData['city'],
            ]);

            FamilyDetail::create([
                'profile_id' => $profile->id,
                'father_occupation' => ['Business', 'Retired', 'Government Service', 'Doctor', 'Engineer'][rand(0, 4)],
                'mother_occupation' => ['Homemaker', 'Teacher', 'Government Service', 'Business'][rand(0, 3)],
                'family_type' => ['joint', 'nuclear'][rand(0, 1)],
                'family_values' => ['traditional', 'moderate', 'liberal'][rand(0, 2)],
                'num_brothers' => rand(0, 3),
                'num_sisters' => rand(0, 3),
            ]);

            ContactInfo::create([
                'profile_id' => $profile->id,
                'primary_phone' => $phone,
                'email' => $email,
                'city' => $cityData['city'],
                'state' => $cityData['state'],
            ]);

            LifestyleInfo::create([
                'profile_id' => $profile->id,
                'diet' => ['vegetarian', 'non_vegetarian', 'eggetarian', 'vegan'][rand(0, 3)],
                'smoking' => 'no',
                'drinking' => ['no', 'occasionally'][rand(0, 1)],
            ]);

            // Generate + attach avatar
            $this->generateAvatar($profile, $first, $last);

            $this->memberUsers[] = $user;
            $this->memberProfiles[] = $profile;
        }
    }

    private function pickReligion(): string
    {
        $roll = rand(1, 100);
        $cumulative = 0;
        foreach ($this->religionsDist as $r) {
            $cumulative += $r['weight'];
            if ($roll <= $cumulative) {
                return $r['religion'];
            }
        }
        return 'Hindu';
    }

    private function casteForReligion(string $religion): ?string
    {
        return match ($religion) {
            'Hindu' => ['Brahmin', 'Kshatriya', 'Vaishya', 'Kayastha', 'Reddy', 'Nair', 'Iyer'][rand(0, 6)],
            'Christian' => ['Catholic', 'Protestant', 'Orthodox'][rand(0, 2)],
            'Muslim' => ['Sunni', 'Shia'][rand(0, 1)],
            'Jain' => ['Digambar', 'Shwetambar'][rand(0, 1)],
            default => null,
        };
    }

    private function pickEmployer(): string
    {
        $gov = ['Government of India', 'State Government'];
        $private = ['TCS', 'Infosys', 'Wipro', 'Reliance', 'HDFC Bank', 'ICICI Bank', 'Accenture'];
        $self = ['Self-employed', 'Own Business'];

        return rand(1, 100) <= 15
            ? $gov[array_rand($gov)]
            : (rand(1, 100) <= 60 ? $private[array_rand($private)] : $self[array_rand($self)]);
    }

    // ----------------------------------------------------------------
    // AVATAR GENERATION (GD)
    // ----------------------------------------------------------------

    private function generateAvatar(Profile $profile, string $first, string $last): void
    {
        $initials = strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
        $bgHex = $this->avatarColors[array_rand($this->avatarColors)];

        $size = 400;
        $img = imagecreatetruecolor($size, $size);

        [$r, $g, $b] = sscanf($bgHex, '#%02x%02x%02x');
        $bg = imagecolorallocate($img, $r, $g, $b);
        imagefill($img, 0, 0, $bg);

        $white = imagecolorallocate($img, 255, 255, 255);
        $fontSize = 5; // Built-in GD font (5 is largest)
        // Scale text visually with imagestring — repeat to make it bigger-looking
        // since GD built-in fonts top out at size 5 (~10x18 px).
        // Use imagettftext only if a TTF exists — otherwise use imagestring centered.
        $textW = imagefontwidth($fontSize) * strlen($initials);
        $textH = imagefontheight($fontSize);
        $x = (int) (($size - $textW) / 2);
        $y = (int) (($size - $textH) / 2);

        // To make initials visible on a 400x400 canvas, draw a larger temporary
        // canvas with initials, then downsample — cheap upscaling of bitmap font.
        $tmp = imagecreatetruecolor(80, 80);
        $tmpBg = imagecolorallocate($tmp, $r, $g, $b);
        imagefill($tmp, 0, 0, $tmpBg);
        $tmpWhite = imagecolorallocate($tmp, 255, 255, 255);
        $tmpX = (int) ((80 - $textW) / 2);
        $tmpY = (int) ((80 - $textH) / 2);
        imagestring($tmp, $fontSize, $tmpX, $tmpY, $initials, $tmpWhite);
        imagecopyresampled($img, $tmp, 0, 0, 0, 0, $size, $size, 80, 80);
        imagedestroy($tmp);

        $relPath = "demo-avatars/profile-{$profile->id}.png";
        $fullPath = storage_path('app/public/' . $relPath);
        imagepng($img, $fullPath);
        imagedestroy($img);

        $url = '/storage/' . $relPath;

        ProfilePhoto::create([
            'profile_id' => $profile->id,
            'photo_type' => 'profile',
            'photo_url' => $url,
            'thumbnail_url' => $url,
            'medium_url' => $url,
            'original_url' => $url,
            'storage_driver' => 'public',
            'is_primary' => true,
            'is_visible' => true,
            'display_order' => 1,
            'approval_status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    // ----------------------------------------------------------------
    // LEADS + CALL LOGS
    // ----------------------------------------------------------------

    private function seedLeadsAndCalls(): void
    {
        if (empty($this->staffUsers['telecaller'])) {
            return;
        }
        $telecallers = $this->staffUsers['telecaller'];

        // Pre-pick ~10 members to be "converted leads" so the stats chain:
        // lead → converted → registered profile
        $convertedProfileIndices = array_rand($this->memberProfiles, min(10, count($this->memberProfiles)));
        if (! is_array($convertedProfileIndices)) {
            $convertedProfileIndices = [$convertedProfileIndices];
        }
        $convertedProfiles = array_map(fn ($i) => $this->memberProfiles[$i], $convertedProfileIndices);

        $statusDist = [
            'registered' => 10, // matches $convertedProfiles count
            'interested' => 6,
            'contacted' => 8,
            'new' => 5,
            'not_interested' => 2,
            'lost' => 2,
        ];

        $leads = [];
        $convertedIdx = 0;

        foreach ($statusDist as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                $gender = rand(1, 100) <= 60 ? 'female' : 'male';
                $first = $gender === 'male'
                    ? $this->maleFirstNames[array_rand($this->maleFirstNames)]
                    : $this->femaleFirstNames[array_rand($this->femaleFirstNames)];
                $last = $this->surnames[array_rand($this->surnames)];
                $telecaller = $telecallers[array_rand($telecallers)];

                $leadData = [
                    'full_name' => "{$first} {$last}",
                    'phone' => '+91 8' . rand(100000000, 999999999),
                    'email' => strtolower("{$first}.{$last}") . rand(100, 999) . '@demo.local',
                    'gender' => $gender,
                    'age' => rand(22, 38),
                    'source' => ['walk_in', 'phone', 'website', 'referral', 'whatsapp', 'advertisement'][rand(0, 5)],
                    'status' => $status,
                    'assigned_to_staff_id' => $telecaller->id,
                    'created_by_staff_id' => $telecaller->id,
                    'branch_id' => $telecaller->branch_id,
                    'notes' => "Demo lead — {$status} status",
                    'follow_up_date' => in_array($status, ['new', 'contacted', 'interested'])
                        ? now()->addDays(rand(-3, 10))->toDateString()
                        : null,
                ];

                if ($status === 'registered' && $convertedIdx < count($convertedProfiles)) {
                    $profile = $convertedProfiles[$convertedIdx++];
                    $leadData['profile_id'] = $profile->id;
                    $leadData['converted_at'] = now()->subDays(rand(1, 25));
                    $leadData['converted_by_staff_id'] = $telecaller->id;
                }

                $leads[] = Lead::create($leadData);
            }
        }

        // Call logs — spread across last 30 days, tied to random leads + telecallers
        for ($i = 0; $i < $this->callLogCount; $i++) {
            $lead = $leads[array_rand($leads)];
            $staff = User::find($lead->assigned_to_staff_id);
            if (! $staff) {
                continue;
            }

            $outcomes = ['connected', 'connected', 'connected', 'no_answer', 'busy', 'voicemail', 'interested', 'not_interested'];
            $outcome = $outcomes[array_rand($outcomes)];

            CallLog::create([
                'lead_id' => $lead->id,
                'called_by_staff_id' => $staff->id,
                'call_type' => rand(1, 100) <= 85 ? 'outgoing' : 'incoming',
                'duration_minutes' => $outcome === 'connected' ? rand(2, 15) : ($outcome === 'no_answer' ? 0 : rand(1, 3)),
                'outcome' => $outcome,
                'called_at' => now()->subDays(rand(0, 29))->subHours(rand(0, 23)),
                'follow_up_required' => rand(1, 100) <= 30,
                'follow_up_date' => rand(1, 100) <= 30 ? now()->addDays(rand(1, 7))->toDateString() : null,
                'notes' => "Demo call log",
            ]);
        }
    }

    // ----------------------------------------------------------------
    // SUBSCRIPTIONS
    // ----------------------------------------------------------------

    private function seedSubscriptions(): void
    {
        $paidPlans = collect($this->plans)->filter(fn ($p) => ($p['price_inr'] ?? 0) > 0)->values()->toArray();
        if (empty($paidPlans)) {
            return;
        }

        // Pick ~30% of members to have subscriptions
        $subscribingMembers = array_rand($this->memberUsers, min($this->subscriptionCount, count($this->memberUsers)));
        if (! is_array($subscribingMembers)) {
            $subscribingMembers = [$subscribingMembers];
        }

        foreach ($subscribingMembers as $idx) {
            $user = $this->memberUsers[$idx];
            $plan = $paidPlans[array_rand($paidPlans)];
            $durationMonths = $plan['duration_months'] ?? 3;
            $daysAgo = rand(0, 150);
            $startsAt = now()->subDays($daysAgo);
            $expiresAt = $startsAt->copy()->addMonths($durationMonths);

            // Most are paid, 3 pending
            $paymentStatus = rand(1, 100) <= 80 ? 'paid' : 'pending';
            $amountPaise = (int) ($plan['price_inr'] ?? 0) * 100;

            Subscription::create([
                'user_id' => $user->id,
                'branch_id' => $user->branch_id,
                'plan_id' => $plan['id'],
                'plan_name' => $plan['plan_name'] ?? 'Plan',
                'original_amount' => $amountPaise,
                'amount' => $amountPaise,
                'payment_status' => $paymentStatus,
                'razorpay_order_id' => $paymentStatus === 'paid' ? 'order_DEMO_' . uniqid() : null,
                'razorpay_payment_id' => $paymentStatus === 'paid' ? 'pay_DEMO_' . uniqid() : null,
                'starts_at' => $startsAt->toDateString(),
                'expires_at' => $expiresAt->toDateString(),
                'is_active' => $paymentStatus === 'paid' && $expiresAt->isFuture(),
                'created_at' => $startsAt,
            ]);
        }
    }

    // ----------------------------------------------------------------
    // STAFF TARGETS
    // ----------------------------------------------------------------

    private function seedStaffTargets(): void
    {
        $targetStaff = array_merge(
            $this->staffUsers['telecaller'] ?? [],
            $this->staffUsers['branch_manager'] ?? []
        );

        $currentMonth = now()->startOfMonth();
        $prevMonth = now()->subMonth()->startOfMonth();

        foreach ($targetStaff as $staff) {
            // Current month
            StaffTarget::firstOrCreate(
                ['staff_user_id' => $staff->id, 'month' => $currentMonth->toDateString()],
                [
                    'branch_id' => $staff->branch_id,
                    'registration_target' => 20,
                    'revenue_target' => 5000000, // ₹50,000 in paise
                    'call_target' => 100,
                    'incentive_per_registration' => 20000, // ₹200 in paise
                    'incentive_per_subscription_pct' => 10.00,
                    'notes' => 'Demo target',
                ]
            );
            // Previous month (for comparison charts)
            StaffTarget::firstOrCreate(
                ['staff_user_id' => $staff->id, 'month' => $prevMonth->toDateString()],
                [
                    'branch_id' => $staff->branch_id,
                    'registration_target' => 18,
                    'revenue_target' => 4500000,
                    'call_target' => 90,
                    'incentive_per_registration' => 20000,
                    'incentive_per_subscription_pct' => 10.00,
                    'notes' => 'Demo target (previous month)',
                ]
            );
        }
    }

    // ----------------------------------------------------------------
    // INTERESTS
    // ----------------------------------------------------------------

    private function seedInterests(): void
    {
        if (count($this->memberProfiles) < 2) {
            return;
        }

        $statusDist = ['pending' => 10, 'accepted' => 7, 'declined' => 3];
        $created = 0;

        foreach ($statusDist as $status => $count) {
            for ($i = 0; $i < $count && $created < $this->interestCount; $i++) {
                do {
                    $sender = $this->memberProfiles[array_rand($this->memberProfiles)];
                    $receiver = $this->memberProfiles[array_rand($this->memberProfiles)];
                } while ($sender->id === $receiver->id);

                // Avoid duplicate pairs
                $exists = Interest::where('sender_profile_id', $sender->id)
                    ->where('receiver_profile_id', $receiver->id)
                    ->exists();
                if ($exists) {
                    continue;
                }

                Interest::create([
                    'sender_profile_id' => $sender->id,
                    'receiver_profile_id' => $receiver->id,
                    'status' => $status,
                    'created_at' => now()->subDays(rand(0, 20)),
                ]);
                $created++;
            }
        }
    }

    // ----------------------------------------------------------------
    // PROFILE VIEWS
    // ----------------------------------------------------------------

    private function seedProfileViews(): void
    {
        if (count($this->memberProfiles) < 2) {
            return;
        }

        for ($i = 0; $i < $this->profileViewCount; $i++) {
            do {
                $viewer = $this->memberProfiles[array_rand($this->memberProfiles)];
                $viewed = $this->memberProfiles[array_rand($this->memberProfiles)];
            } while ($viewer->id === $viewed->id);

            ProfileView::create([
                'viewer_profile_id' => $viewer->id,
                'viewed_profile_id' => $viewed->id,
                'viewed_at' => now()->subDays(rand(0, 7))->subHours(rand(0, 23)),
            ]);
        }
    }

    // ----------------------------------------------------------------
    // TESTIMONIALS (marked as demo so clean command can remove)
    // ----------------------------------------------------------------

    private function seedTestimonials(): void
    {
        $stories = [
            ['names' => '[Demo] Arjun & Priya', 'story' => 'We met on this platform six months ago and got engaged last month. Thank you for the wonderful matching experience!', 'location' => 'Mumbai'],
            ['names' => '[Demo] Rahul & Anjali', 'story' => 'Thanks to the verified profiles and secure messaging, we found each other within three months. Getting married next spring!', 'location' => 'Bengaluru'],
            ['names' => '[Demo] Karthik & Divya', 'story' => 'The match recommendations were spot on. We share the same values and family traditions. Very grateful.', 'location' => 'Chennai'],
        ];

        foreach ($stories as $i => $story) {
            Testimonial::firstOrCreate(
                ['couple_names' => $story['names']],
                [
                    'story' => $story['story'],
                    'location' => $story['location'],
                    'wedding_date' => now()->subMonths(rand(1, 12))->toDateString(),
                    'is_visible' => true,
                    'display_order' => 10 + $i,
                ]
            );
        }
    }

    // ----------------------------------------------------------------
    // SUMMARY
    // ----------------------------------------------------------------

    private function printSummary(): void
    {
        if (! $this->command) {
            return;
        }

        $this->command->newLine();
        $this->command->info('Summary:');
        $this->command->line("  Branches:      1 demo branch added (Head Office untouched)");
        $this->command->line("  Staff:         " . array_sum(array_map('count', $this->staffUsers)) . " demo staff users");
        $this->command->line("  Members:       " . count($this->memberUsers) . " demo members with profiles + avatars");
        $this->command->line("  Leads:         " . Lead::where('notes', 'LIKE', 'Demo%')->count() . " demo leads");
        $this->command->line("  Call Logs:     " . CallLog::where('notes', 'Demo call log')->count() . " demo call logs");
        $this->command->line("  Subscriptions: " . Subscription::where('razorpay_order_id', 'LIKE', 'order_DEMO_%')->orWhereNull('razorpay_order_id')->count() . " demo subscriptions");
        $this->command->line("  Staff Targets: " . StaffTarget::where('notes', 'LIKE', 'Demo target%')->count() . " demo targets");
        $this->command->line("  Testimonials:  " . Testimonial::where('couple_names', 'LIKE', '[Demo]%')->count() . " demo testimonials");
        $this->command->newLine();
        $this->command->line('To remove all demo data: php artisan matrimony:demo-clean');
    }
}
