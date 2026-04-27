<?php

use App\Http\Controllers\Api\V1\SuccessStoryController;
use App\Models\Testimonial;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| SuccessStoryController — GET (public) + POST (auth) /api/v1/success-stories
|--------------------------------------------------------------------------
| Real `testimonials` table; UploadedFile::fake + Storage::fake('public')
| for the photo-upload path.
*/

function buildStoryUser(int $id = 7700): User
{
    $u = new User();
    $u->exists = true;
    $u->forceFill(['id' => $id, 'email' => "s{$id}@e.com", 'is_active' => true]);
    $u->setRelation('userMemberships', new EloquentCollection());
    return $u;
}

function storyRequest(?User $user, string $method = 'GET', array $body = [], array $files = [], array $query = []): Request
{
    $r = Request::create(
        '/api/v1/success-stories',
        $method,
        $method === 'GET' ? $query : $body,
        [],
        $files,
    );
    if ($user) {
        $r->setUserResolver(fn () => $user);
    }
    return $r;
}

beforeEach(function () {
    if (! Schema::hasTable('testimonials')) {
        Schema::create('testimonials', function (Blueprint $t) {
            $t->id();
            $t->string('couple_names', 200);
            $t->text('story');
            $t->string('photo_url', 500)->nullable();
            $t->date('wedding_date')->nullable();
            $t->string('location', 100)->nullable();
            $t->unsignedBigInteger('submitted_by_user_id')->nullable();
            $t->boolean('is_visible')->default(false);
            $t->tinyInteger('display_order')->unsigned()->default(0);
            $t->timestamps();
        });
    }
    Storage::fake('public');
});

afterEach(function () {
    Schema::dropIfExists('testimonials');
});

/* ==================================================================
 |  GET /success-stories — public feed
 | ================================================================== */

it('index returns approved stories only', function () {
    Testimonial::create([
        'couple_names' => 'Visible Couple', 'story' => 'love story',
        'wedding_date' => Carbon::parse('2026-02-14'),
        'submitted_by_user_id' => 100, 'is_visible' => true, 'display_order' => 0,
    ]);
    Testimonial::create([
        'couple_names' => 'Hidden Couple', 'story' => 'pending review',
        'submitted_by_user_id' => 101, 'is_visible' => false, 'display_order' => 0,
    ]);

    $response = app(SuccessStoryController::class)->index(storyRequest(null));
    $items = $response->getData(true)['data'];

    expect($response->getStatusCode())->toBe(200);
    expect($items)->toHaveCount(1);
    expect($items[0]['couple_names'])->toBe('Visible Couple');
});

it('index returns empty array when no approved stories exist', function () {
    Testimonial::create([
        'couple_names' => 'Pending Only', 'story' => 'pending',
        'submitted_by_user_id' => 100, 'is_visible' => false, 'display_order' => 0,
    ]);

    $response = app(SuccessStoryController::class)->index(storyRequest(null));

    expect($response->getData(true)['data'])->toBe([]);
    expect($response->getData(true)['meta']['total'])->toBe(0);
});

it('index meta carries pagination totals', function () {
    foreach (range(1, 15) as $i) {
        Testimonial::create([
            'couple_names' => "Couple {$i}", 'story' => "story {$i}",
            'submitted_by_user_id' => 100 + $i, 'is_visible' => true, 'display_order' => $i,
        ]);
    }

    $response = app(SuccessStoryController::class)->index(storyRequest(null));
    $meta = $response->getData(true)['meta'];

    expect($meta['total'])->toBe(15);
    expect($meta['per_page'])->toBe(10);
    expect($meta['last_page'])->toBe(2);
});

it('index renders absolute photo_url when photo is set', function () {
    Storage::disk('public')->put('success-stories/p1.jpg', 'fake-bytes');
    Testimonial::create([
        'couple_names' => 'Anita & Ravi', 'story' => 'love',
        'photo_url' => 'success-stories/p1.jpg',
        'submitted_by_user_id' => 100, 'is_visible' => true, 'display_order' => 0,
    ]);

    $items = app(SuccessStoryController::class)->index(storyRequest(null))->getData(true)['data'];

    expect($items[0]['photo_url'])->toContain('success-stories/p1.jpg');
});

/* ==================================================================
 |  POST /success-stories — authenticated submission
 | ================================================================== */

it('store persists a story with is_visible=false (pending admin)', function () {
    $user = buildStoryUser();

    $response = app(SuccessStoryController::class)->store(storyRequest(
        $user, 'POST',
        body: [
            'couple_names' => 'Anita & Ravi',
            'story' => str_repeat('We met online and got married. ', 3),  // > 20 chars
            'wedding_date' => '2026-02-14',
            'location' => 'Mumbai',
        ],
    ));
    $payload = $response->getData(true)['data'];

    expect($response->getStatusCode())->toBe(201);
    expect($payload['status'])->toBe('pending');
    expect($payload['message'])->toContain('review');

    $row = Testimonial::find($payload['story_id']);
    expect($row->couple_names)->toBe('Anita & Ravi');
    expect($row->is_visible)->toBeFalse();
    expect($row->submitted_by_user_id)->toBe($user->id);
});

it('store accepts a photo upload + persists path', function () {
    $user = buildStoryUser();

    $response = app(SuccessStoryController::class)->store(storyRequest(
        $user, 'POST',
        body: [
            'couple_names' => 'Anita & Ravi',
            'story' => str_repeat('Happy story. ', 3),
        ],
        files: ['photo' => UploadedFile::fake()->image('couple.jpg', 800, 600)->size(500)],
    ));
    $row = Testimonial::find($response->getData(true)['data']['story_id']);

    expect($row->photo_url)->toStartWith('success-stories/');
    Storage::disk('public')->assertExists($row->photo_url);
});

it('store rejects story shorter than 20 chars', function () {
    $user = buildStoryUser();

    expect(fn () => app(SuccessStoryController::class)->store(storyRequest(
        $user, 'POST',
        body: ['couple_names' => 'A & B', 'story' => 'too short'],
    )))->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('store rejects future wedding_date', function () {
    $user = buildStoryUser();

    expect(fn () => app(SuccessStoryController::class)->store(storyRequest(
        $user, 'POST',
        body: [
            'couple_names' => 'A & B',
            'story' => str_repeat('long enough story. ', 3),
            'wedding_date' => Carbon::now()->addYear()->toDateString(),
        ],
    )))->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('store rejects oversized photo (> 3 MB)', function () {
    $user = buildStoryUser();

    expect(fn () => app(SuccessStoryController::class)->store(storyRequest(
        $user, 'POST',
        body: [
            'couple_names' => 'A & B',
            'story' => str_repeat('long enough story. ', 3),
        ],
        files: ['photo' => UploadedFile::fake()->image('huge.jpg')->size(4000)],  // 4 MB
    )))->toThrow(\Illuminate\Validation\ValidationException::class);
});
