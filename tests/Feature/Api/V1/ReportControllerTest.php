<?php

use App\Http\Controllers\Api\V1\ReportController;
use App\Models\Profile;
use App\Models\ProfileReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| ReportController — POST /api/v1/profiles/{matriId}/report
|--------------------------------------------------------------------------
| Reuses the test-seam pattern (`findTargetByMatriId`) so we don't need
| a profiles table. The report row IS persisted to inline `profile_reports`
| so we exercise the real validation + dupe-check + create flow.
*/

function buildReportUser(int $id, bool $withProfile = true): User
{
    $u = new User();
    $u->exists = true;
    $u->forceFill(['id' => $id, 'email' => "r{$id}@e.com", 'is_active' => true]);
    $u->setRelation('userMemberships', new EloquentCollection());

    if ($withProfile) {
        $u->setRelation('profile', buildReportProfile($id, $u));
    } else {
        $u->setRelation('profile', null);
    }

    return $u;
}

function buildReportProfile(int $id, ?User $user = null, ?string $matriId = null): Profile
{
    $user ??= (function () use ($id) {
        $u = new User();
        $u->exists = true;
        $u->forceFill(['id' => $id, 'email' => "stub{$id}@e.com", 'is_active' => true]);
        return $u;
    })();

    $p = new Profile();
    $p->exists = true;
    $p->forceFill([
        'id' => $id,
        'user_id' => $user->id,
        'matri_id' => $matriId ?? 'AM'.str_pad((string) $id, 6, '0', STR_PAD_LEFT),
        'gender' => 'male',
        'date_of_birth' => Carbon::parse('1995-01-01'),
        'is_active' => true,
        'is_approved' => true,
    ]);
    $p->setRelation('user', $user);

    return $p;
}

function buildReportController(array $matriIdMap = []): ReportController
{
    return new class($matriIdMap) extends ReportController {
        public function __construct(private array $matriIdMap) {}

        protected function findTargetByMatriId(string $matriId): ?Profile
        {
            return $this->matriIdMap[$matriId] ?? null;
        }
    };
}

function reportRequest(User $user, array $body = []): Request
{
    $r = Request::create('/api/v1/profiles/AM000201/report', 'POST', $body);
    $r->setUserResolver(fn () => $user);

    return $r;
}

beforeEach(function () {
    if (! Schema::hasTable('profile_reports')) {
        Schema::create('profile_reports', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('reporter_profile_id');
            $t->unsignedBigInteger('reported_profile_id');
            $t->string('reason', 50);
            $t->text('description')->nullable();
            $t->string('status', 20)->default('pending');
            $t->text('admin_notes')->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
        });
    }
});

afterEach(function () {
    Schema::dropIfExists('profile_reports');
});

/* ==================================================================
 |  Guards
 | ================================================================== */

it('returns 422 PROFILE_REQUIRED when viewer has no profile', function () {
    $user = buildReportUser(100, withProfile: false);

    $response = buildReportController()->store(
        reportRequest($user, ['reason' => 'fake_profile']),
        'AM000201',
    );

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('PROFILE_REQUIRED');
});

it('returns 422 VALIDATION_FAILED when reason is missing', function () {
    $user = buildReportUser(100);

    expect(fn () => buildReportController()->store(reportRequest($user, []), 'AM000201'))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('returns 422 VALIDATION_FAILED when reason is not in the canonical enum', function () {
    $user = buildReportUser(100);

    expect(fn () => buildReportController()->store(
        reportRequest($user, ['reason' => 'something_made_up']),
        'AM000201',
    ))->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('returns 404 when target matri_id is unknown', function () {
    $user = buildReportUser(100);

    $response = buildReportController()
        ->store(reportRequest($user, ['reason' => 'fake_profile']), 'AM999999');

    expect($response->getStatusCode())->toBe(404);
});

it('returns 422 INVALID_TARGET on self-report', function () {
    $user = buildReportUser(100);
    $self = $user->profile;

    $response = buildReportController(matriIdMap: [$self->matri_id => $self])
        ->store(reportRequest($user, ['reason' => 'fake_profile']), $self->matri_id);

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('INVALID_TARGET');
});

/* ==================================================================
 |  Happy path + dupe guard
 | ================================================================== */

it('creates a profile_report row + returns 201 with report_id', function () {
    $user = buildReportUser(100);
    $target = buildReportProfile(201, matriId: 'AM000201');

    $response = buildReportController(matriIdMap: ['AM000201' => $target])
        ->store(reportRequest($user, [
            'reason' => 'harassment',
            'description' => 'sent inappropriate messages',
        ]), 'AM000201');
    $payload = $response->getData(true);

    expect($response->getStatusCode())->toBe(201);
    expect($payload['data'])->toHaveKeys(['report_id', 'status', 'message']);
    expect($payload['data']['status'])->toBe('pending');
    expect($payload['data']['report_id'])->toBeInt();

    $row = ProfileReport::find($payload['data']['report_id']);
    expect($row->reporter_profile_id)->toBe(100);
    expect($row->reported_profile_id)->toBe(201);
    expect($row->reason)->toBe('harassment');
    expect($row->description)->toBe('sent inappropriate messages');
    expect($row->status)->toBe('pending');
});

it('returns 409 ALREADY_EXISTS when a pending report from this viewer for this target already exists', function () {
    $user = buildReportUser(100);
    $target = buildReportProfile(201, matriId: 'AM000201');

    // Pre-seed a pending report.
    ProfileReport::create([
        'reporter_profile_id' => 100,
        'reported_profile_id' => 201,
        'reason' => 'fake_profile',
        'status' => 'pending',
    ]);

    $response = buildReportController(matriIdMap: ['AM000201' => $target])
        ->store(reportRequest($user, ['reason' => 'harassment']), 'AM000201');

    expect($response->getStatusCode())->toBe(409);
    expect($response->getData(true)['error']['code'])->toBe('ALREADY_EXISTS');

    // No new row was created.
    expect(ProfileReport::count())->toBe(1);
});

it('allows a fresh report after a previous one has been reviewed (status != pending)', function () {
    $user = buildReportUser(100);
    $target = buildReportProfile(201, matriId: 'AM000201');

    // Pre-seed a CLOSED report (admin already reviewed).
    ProfileReport::create([
        'reporter_profile_id' => 100,
        'reported_profile_id' => 201,
        'reason' => 'fake_profile',
        'status' => 'dismissed',
    ]);

    $response = buildReportController(matriIdMap: ['AM000201' => $target])
        ->store(reportRequest($user, ['reason' => 'harassment']), 'AM000201');

    expect($response->getStatusCode())->toBe(201);
    expect(ProfileReport::count())->toBe(2);
});
