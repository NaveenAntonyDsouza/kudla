<?php

use App\Models\Profile;
use App\Support\AlternateGenders;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;

/*
| Owner's choice, 4 Nov 2026: visitors' mixed lists (Discover pages, public
| search without Bride/Groom, homepage featured) alternate a bride and a groom,
| bride first, each side in its own order; when one side runs out the other
| carries on. Kudla has about three men per woman, so the lists looked all-men.
*/

afterEach(function () {
    Schema::dropIfExists('profiles');
    Paginator::currentPageResolver(fn () => 1);
});

it('alternates bride, groom, keeping each side in order, then the rest of the bigger side', function () {
    // ids in query order: m1 m2 f3 m4 f5 m6 m7
    $order = AlternateGenders::order([1 => 'male', 2 => 'male', 3 => 'female', 4 => 'male', 5 => 'female', 6 => 'male', 7 => 'male']);

    expect($order)->toBe([3, 1, 5, 2, 4, 6, 7]);
    expect(AlternateGenders::order([1 => 'male', 2 => 'male']))->toBe([1, 2])
        ->and(AlternateGenders::order([]))->toBe([]);
});

it('pages through the alternating list with the right total', function () {
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->string('gender');
        $t->softDeletes();
        $t->timestamps();
    });
    // 3 brides, 7 grooms, in id order
    foreach (['male', 'male', 'female', 'male', 'female', 'male', 'male', 'female', 'male', 'male'] as $i => $gender) {
        DB::table('profiles')->insert(['id' => $i + 1, 'gender' => $gender]);
    }
    $query = fn () => Profile::query()->orderBy('profiles.id');

    $first = AlternateGenders::paginate($query(), 4);
    expect($first->total())->toBe(10)
        ->and($first->lastPage())->toBe(3)
        ->and($first->pluck('id')->all())->toBe([3, 1, 5, 2]);

    Paginator::currentPageResolver(fn () => 2);
    expect(AlternateGenders::paginate($query(), 4)->pluck('id')->all())->toBe([8, 4, 6, 7]);

    Paginator::currentPageResolver(fn () => 3);
    expect(AlternateGenders::paginate($query(), 4)->pluck('id')->all())->toBe([9, 10]);

    Paginator::currentPageResolver(fn () => 9);
    expect(AlternateGenders::paginate($query(), 4)->count())->toBe(0);
});

it('is used for visitors on Discover, public search without Bride/Groom, and the homepage', function () {
    expect(file_get_contents(app_path('Http/Controllers/DiscoverController.php')))->toContain('$profile || $side ? $query->paginate(20) : \App\Support\AlternateGenders::paginate($query, 20)')
        ->and(file_get_contents(app_path('Http/Controllers/SearchController.php')))->toContain('$gender ? $query->paginate(20) : \App\Support\AlternateGenders::paginate($query, 20)')
        ->and(file_get_contents(app_path('Http/Controllers/HomeController.php')))->toContain('\App\Support\AlternateGenders::collection(');
});
