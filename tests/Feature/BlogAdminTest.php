<?php

use App\Filament\Resources\BlogPostResource;
use App\Filament\Resources\BlogPostResource\Pages\CreateBlogPost;
use App\Models\BlogPost;
use App\Models\SiteSetting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

/* Content Management → Blog Posts: an admin writes and publishes an article. */

beforeEach(function () {
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->string('password')->nullable();
        $t->string('role')->nullable();
        $t->unsignedBigInteger('staff_role_id')->nullable();
        $t->boolean('is_active')->default(true);
        $t->rememberToken();
        $t->timestamps();
    });
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
    (require database_path('migrations/2026_10_15_000001_create_blog_posts_table.php'))->up();
    Cache::flush();

    DB::table('users')->insert([
        'id' => 1, 'name' => 'Site Admin', 'email' => 'admin@site.test', 'role' => 'admin',
        'password' => Hash::make('Admin@1234'), 'created_at' => now(), 'updated_at' => now(),
    ]);
    $this->actingAs(User::find(1));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

afterEach(function () {
    foreach (['users', 'site_settings', 'blog_posts'] as $t) {
        Schema::dropIfExists($t);
    }
});

it('lets an admin write and publish an article', function () {
    Livewire::test(CreateBlogPost::class)
        ->fillForm([
            'title' => 'Documents for a Catholic Church Wedding',
            'slug' => 'catholic-church-wedding-documents',
            'category' => 'Church Wedding',
            'body' => '<p>Baptism certificate first.</p>',
            'status' => 'published',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $post = BlogPost::where('slug', 'catholic-church-wedding-documents')->first();

    expect($post)->not->toBeNull()
        ->and($post->isLive())->toBeTrue()
        ->and(BlogPost::hasPublishedPosts())->toBeTrue();
});

it('refuses a web address with spaces or capitals', function () {
    Livewire::test(CreateBlogPost::class)
        ->fillForm(['title' => 'X', 'slug' => 'Bad Address', 'body' => '<p>x</p>', 'status' => 'draft'])
        ->call('create')
        ->assertHasFormErrors(['slug']);
});

it('is open to admins and disappears when the blog is switched off', function () {
    expect(BlogPostResource::canAccess())->toBeTrue();

    SiteSetting::setValue('blog_enabled', '0');
    expect(BlogPostResource::canAccess())->toBeFalse();
});
